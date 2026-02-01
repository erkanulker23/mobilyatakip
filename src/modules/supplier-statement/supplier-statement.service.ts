import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SupplierStatement } from '../../entities/supplier-statement.entity';
import { Purchase } from '../../entities/purchase.entity';
import { SupplierPayment } from '../../entities/supplier-payment.entity';
import { StatementStatus } from '../../common/enums/statement-status.enum';
import { SupplierPaymentService } from '../supplier-payment/supplier-payment.service';

@Injectable()
export class SupplierStatementService {
  constructor(
    @InjectRepository(SupplierStatement)
    private repo: Repository<SupplierStatement>,
    @InjectRepository(Purchase)
    private purchaseRepo: Repository<Purchase>,
    @InjectRepository(SupplierPayment)
    private paymentRepo: Repository<SupplierPayment>,
    private supplierPaymentService: SupplierPaymentService,
  ) {}

  async generate(supplierId: string, startDate: Date, endDate: Date): Promise<SupplierStatement> {
    const purchasesBefore = await this.purchaseRepo
      .createQueryBuilder('p')
      .select('COALESCE(SUM(p.grandTotal), 0)', 'total')
      .where('p.supplierId = :supplierId', { supplierId })
      .andWhere('p.purchaseDate < :start', { start: startDate })
      .getRawOne<{ total: string }>();
    const paymentsBefore = await this.paymentRepo
      .createQueryBuilder('sp')
      .select('COALESCE(SUM(sp.amount), 0)', 'total')
      .where('sp.supplierId = :supplierId', { supplierId })
      .andWhere('sp.paymentDate < :start', { start: startDate })
      .getRawOne<{ total: string }>();
    const openingBalance = Number(purchasesBefore?.total ?? 0) - Number(paymentsBefore?.total ?? 0);

    const purchases = await this.purchaseRepo
      .createQueryBuilder('p')
      .select('COALESCE(SUM(p.grandTotal), 0)', 'total')
      .where('p.supplierId = :supplierId', { supplierId })
      .andWhere('p.purchaseDate BETWEEN :start AND :end', { start: startDate, end: endDate })
      .getRawOne<{ total: string }>();
    const payments = await this.paymentRepo
      .createQueryBuilder('sp')
      .select('COALESCE(SUM(sp.amount), 0)', 'total')
      .where('sp.supplierId = :supplierId', { supplierId })
      .andWhere('sp.paymentDate BETWEEN :start AND :end', { start: startDate, end: endDate })
      .getRawOne<{ total: string }>();
    const totalPurchases = Number(purchases?.total ?? 0);
    const totalPayments = Number(payments?.total ?? 0);
    const st = this.repo.create({
      supplierId,
      startDate,
      endDate,
      openingBalance,
      totalPurchases,
      totalPayments,
      closingBalance: openingBalance + totalPurchases - totalPayments,
      status: StatementStatus.BEKLEMEDE,
    });
    return this.repo.save(st);
  }

  async findBySupplier(supplierId: string): Promise<SupplierStatement[]> {
    return this.repo.find({ where: { supplierId }, order: { createdAt: 'DESC' } });
  }

  async findOne(id: string): Promise<SupplierStatement> {
    const st = await this.repo.findOne({ where: { id }, relations: ['supplier'] });
    if (!st) throw new Error('Mutabakat bulunamadı');
    return st;
  }

  async approve(id: string): Promise<SupplierStatement> {
    const st = await this.repo.findOne({ where: { id } });
    if (!st) throw new Error('Mutabakat bulunamadı');
    st.status = StatementStatus.ONAYLANDI;
    return this.repo.save(st);
  }
}
