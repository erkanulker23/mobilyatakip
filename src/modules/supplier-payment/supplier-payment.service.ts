import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SupplierPayment } from '../../entities/supplier-payment.entity';
import { Supplier } from '../../entities/supplier.entity';
import { PaymentType } from '../../common/enums/payment-type.enum';
import { KasaHareketService } from '../kasa-hareket/kasa-hareket.service';

export interface SupplierBalanceRow {
  supplierId: string;
  supplierName: string;
  totalPurchases: number;
  totalPayments: number;
  balance: number;
}

@Injectable()
export class SupplierPaymentService {
  constructor(
    @InjectRepository(SupplierPayment)
    private repo: Repository<SupplierPayment>,
    @InjectRepository(Supplier)
    private supplierRepo: Repository<Supplier>,
    private kasaHareketService: KasaHareketService,
  ) {}

  async create(data: {
    supplierId: string;
    amount: number;
    paymentDate: string;
    paymentType?: PaymentType;
    reference?: string;
    notes?: string;
    purchaseId?: string;
    kasaId?: string;
  }): Promise<SupplierPayment> {
    const p = this.repo.create({
      ...data,
      paymentDate: new Date(data.paymentDate),
      paymentType: data.paymentType ?? PaymentType.NAKIT,
      kasaId: data.kasaId ?? null,
    });
    const saved = await this.repo.save(p);
    if (data.kasaId && data.amount > 0) {
      await this.kasaHareketService.cikis({
        kasaId: data.kasaId,
        amount: data.amount,
        movementDate: data.paymentDate,
        description: data.purchaseId ? 'Tedarikçi ödemesi (Alış)' : 'Tedarikçi ödemesi',
      });
    }
    return saved;
  }

  async findBySupplier(supplierId: string): Promise<SupplierPayment[]> {
    return this.repo.find({ where: { supplierId }, order: { paymentDate: 'DESC' } });
  }

  async getSupplierBalance(supplierId: string): Promise<{ totalPurchases: number; totalPayments: number; balance: number }> {
    const { Purchase } = await import('../../entities/purchase.entity');
    const purchases = await this.repo.manager
      .getRepository(Purchase)
      .createQueryBuilder('p')
      .select('COALESCE(SUM(p.grandTotal), 0)', 'total')
      .where('p.supplierId = :supplierId', { supplierId })
      .getRawOne<{ total: string }>();
    const payments = await this.repo
      .createQueryBuilder('sp')
      .select('COALESCE(SUM(sp.amount), 0)', 'total')
      .where('sp.supplierId = :supplierId', { supplierId })
      .getRawOne<{ total: string }>();
    const totalPurchases = Number(purchases?.total ?? 0);
    const totalPayments = Number(payments?.total ?? 0);
    return { totalPurchases, totalPayments, balance: totalPurchases - totalPayments };
  }

  async getAllSuppliersWithBalance(): Promise<SupplierBalanceRow[]> {
    const { Purchase } = await import('../../entities/purchase.entity');
    const rows = await this.supplierRepo
      .createQueryBuilder('s')
      .leftJoin(Purchase, 'p', 'p.supplierId = s.id')
      .leftJoin(SupplierPayment, 'sp', 'sp.supplierId = s.id')
      .select('s.id', 'supplierId')
      .addSelect('s.name', 'supplierName')
      .addSelect('COALESCE(SUM(p.grandTotal), 0)', 'totalPurchases')
      .addSelect('COALESCE(SUM(sp.amount), 0)', 'totalPayments')
      .addSelect('COALESCE(SUM(p.grandTotal), 0) - COALESCE(SUM(sp.amount), 0)', 'balance')
      .groupBy('s.id')
      .addGroupBy('s.name')
      .orderBy('s.name', 'ASC')
      .getRawMany<{ supplierId: string; supplierName: string; totalPurchases: string; totalPayments: string; balance: string }>();

    return rows.map((r) => ({
      supplierId: r.supplierId,
      supplierName: r.supplierName ?? '',
      totalPurchases: Number(r.totalPurchases ?? 0),
      totalPayments: Number(r.totalPayments ?? 0),
      balance: Number(r.balance ?? 0),
    }));
  }
}
