import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { CustomerPayment } from '../../entities/customer-payment.entity';
import { Sale } from '../../entities/sale.entity';
import { PaymentType } from '../../common/enums/payment-type.enum';
import { KasaHareketService } from '../kasa-hareket/kasa-hareket.service';

@Injectable()
export class CustomerPaymentService {
  constructor(
    @InjectRepository(CustomerPayment)
    private repo: Repository<CustomerPayment>,
    @InjectRepository(Sale)
    private saleRepo: Repository<Sale>,
    private kasaHareketService: KasaHareketService,
  ) {}

  async create(data: {
    customerId: string;
    amount: number;
    paymentDate: string;
    paymentType?: PaymentType;
    reference?: string;
    notes?: string;
    saleId?: string;
    kasaId?: string;
  }): Promise<CustomerPayment> {
    const p = this.repo.create({
      ...data,
      paymentDate: new Date(data.paymentDate),
      paymentType: data.paymentType ?? PaymentType.NAKIT,
      kasaId: data.kasaId ?? null,
    });
    const saved = await this.repo.save(p);
    if (data.saleId) {
      const sale = await this.saleRepo.findOne({ where: { id: data.saleId } });
      if (sale) {
        sale.paidAmount = Number(sale.paidAmount) + data.amount;
        await this.saleRepo.save(sale);
      }
    }
    if (data.kasaId && data.amount > 0) {
      await this.kasaHareketService.giris({
        kasaId: data.kasaId,
        amount: data.amount,
        movementDate: data.paymentDate,
        description: data.saleId ? `Tahsilat (Satış)` : 'Tahsilat',
      });
    }
    return saved;
  }

  async findByCustomer(customerId: string): Promise<CustomerPayment[]> {
    return this.repo.find({ where: { customerId }, order: { paymentDate: 'DESC' } });
  }

  async findLatest(limit: number = 5): Promise<CustomerPayment[]> {
    return this.repo.find({
      relations: ['customer'],
      order: { paymentDate: 'DESC' },
      take: Math.min(limit, 50),
    });
  }

  async getCustomerBalance(customerId: string): Promise<{ totalSales: number; totalPayments: number; balance: number; overdueAmount: number }> {
    const salesRaw = await this.saleRepo
      .createQueryBuilder('s')
      .select('COALESCE(SUM(s.grandTotal), 0)', 'total')
      .where('s.customerId = :customerId', { customerId })
      .getRawOne<{ total: string }>();
    const payments = await this.repo
      .createQueryBuilder('cp')
      .select('COALESCE(SUM(cp.amount), 0)', 'total')
      .where('cp.customerId = :customerId', { customerId })
      .getRawOne<{ total: string }>();
    const totalSales = Number(salesRaw?.total ?? 0);
    const totalPayments = Number(payments?.total ?? 0);
    // Bakiye = satışlar - alınan ödemeler. saleId ile kaydedilen ödemeler hem sale.paidAmount hem customer_payments'ta olduğu için çift sayım yapılmaz; sadece totalPayments kullanılır.
    const balance = totalSales - totalPayments;
    const overdue = await this.saleRepo
      .createQueryBuilder('s')
      .select('COALESCE(SUM(s.grandTotal - s.paidAmount), 0)', 'total')
      .where('s.customerId = :customerId', { customerId })
      .andWhere('s.dueDate < :now', { now: new Date() })
      .andWhere('(s.grandTotal - s.paidAmount) > 0')
      .getRawOne<{ total: string }>();
    return {
      totalSales,
      totalPayments,
      balance,
      overdueAmount: Number(overdue?.total ?? 0),
    };
  }

  /** Tüm müşteriler için bakiye ve satış sayısı (liste sayfalarında kullanım). */
  async getBalancesAndSalesCountMap(customerIds: string[]): Promise<Map<string, { balance: number; salesCount: number }>> {
    const map = new Map<string, { balance: number; salesCount: number }>();
    if (!customerIds.length) return map;
    const salesAgg = await this.saleRepo
      .createQueryBuilder('s')
      .select('s.customerId', 'customerId')
      .addSelect('COALESCE(SUM(s.grandTotal), 0)', 'totalSales')
      .addSelect('COUNT(s.id)', 'salesCount')
      .where('s.customerId IN (:...ids)', { ids: customerIds })
      .groupBy('s.customerId')
      .getRawMany<{ customerId: string; totalSales: string; salesCount: string }>();
    const paymentsAgg = await this.repo
      .createQueryBuilder('cp')
      .select('cp.customerId', 'customerId')
      .addSelect('COALESCE(SUM(cp.amount), 0)', 'total')
      .where('cp.customerId IN (:...ids)', { ids: customerIds })
      .groupBy('cp.customerId')
      .getRawMany<{ customerId: string; total: string }>();
    const paymentMap = new Map(paymentsAgg.map((p) => [p.customerId, Number(p.total)]));
    for (const row of salesAgg) {
      const totalSales = Number(row.totalSales);
      const totalPayments = paymentMap.get(row.customerId) ?? 0;
      map.set(row.customerId, {
        balance: totalSales - totalPayments,
        salesCount: Number(row.salesCount ?? 0),
      });
    }
    for (const id of customerIds) {
      if (!map.has(id)) map.set(id, { balance: 0, salesCount: 0 });
    }
    return map;
  }

  async getCustomersWithDebt(): Promise<Array<{ customerId: string; balance: number; totalSales: number; totalPayments: number }>> {
    const salesByCustomer = await this.saleRepo
      .createQueryBuilder('s')
      .select('s.customerId', 'customerId')
      .addSelect('COALESCE(SUM(s.grandTotal), 0)', 'totalSales')
      .groupBy('s.customerId')
      .getRawMany<{ customerId: string; totalSales: string }>();
    const paymentsByCustomer = await this.repo
      .createQueryBuilder('cp')
      .select('cp.customerId', 'customerId')
      .addSelect('COALESCE(SUM(cp.amount), 0)', 'total')
      .groupBy('cp.customerId')
      .getRawMany<{ customerId: string; total: string }>();
    const paymentMap = new Map(paymentsByCustomer.map((p) => [p.customerId, Number(p.total)]));
    const result: Array<{ customerId: string; balance: number; totalSales: number; totalPayments: number }> = [];
    for (const row of salesByCustomer) {
      const totalSales = Number(row.totalSales);
      const payments = paymentMap.get(row.customerId) ?? 0;
      const balance = totalSales - payments;
      if (balance > 0) {
        result.push({
          customerId: row.customerId,
          balance,
          totalSales,
          totalPayments: payments,
        });
      }
    }
    return result;
  }
}
