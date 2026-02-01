import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Sale } from '../../entities/sale.entity';
import { Purchase } from '../../entities/purchase.entity';
import { Expense } from '../../entities/expense.entity';
import { CustomerPayment } from '../../entities/customer-payment.entity';
import { SupplierPayment } from '../../entities/supplier-payment.entity';

export interface ReportSummaryDto {
  from: string;
  to: string;
  sales: { total: number; count: number };
  purchases: { total: number; count: number };
  expenses: { total: number; count: number };
}

export interface IncomeExpenseDto {
  from: string;
  to: string;
  income: { sales: number; customerPayments: number; total: number };
  expense: { purchases: number; expenses: number; supplierPayments: number; total: number };
  net: number;
}

@Injectable()
export class ReportsService {
  constructor(
    @InjectRepository(Sale)
    private saleRepo: Repository<Sale>,
    @InjectRepository(Purchase)
    private purchaseRepo: Repository<Purchase>,
    @InjectRepository(Expense)
    private expenseRepo: Repository<Expense>,
    @InjectRepository(CustomerPayment)
    private customerPaymentRepo: Repository<CustomerPayment>,
    @InjectRepository(SupplierPayment)
    private supplierPaymentRepo: Repository<SupplierPayment>,
  ) {}

  async getSummary(from: string, to: string): Promise<ReportSummaryDto> {
    const fromDate = new Date(from);
    const toDate = new Date(to);
    toDate.setHours(23, 59, 59, 999);

    const salesRaw = await this.saleRepo
      .createQueryBuilder('s')
      .select('COALESCE(SUM(s.grandTotal), 0)', 'sum')
      .addSelect('COUNT(s.id)', 'count')
      .where('s.saleDate >= :from', { from: fromDate })
      .andWhere('s.saleDate <= :to', { to: toDate })
      .getRawOne<{ sum: string; count: string }>();
    const salesSum = Number(salesRaw?.sum ?? 0);
    const salesCount = Number(salesRaw?.count ?? 0);

    const purchasesRaw = await this.purchaseRepo
      .createQueryBuilder('p')
      .select('COALESCE(SUM(p.grandTotal), 0)', 'sum')
      .addSelect('COUNT(p.id)', 'count')
      .where('p.purchaseDate >= :from', { from: fromDate })
      .andWhere('p.purchaseDate <= :to', { to: toDate })
      .getRawOne<{ sum: string; count: string }>();
    const purchasesSum = Number(purchasesRaw?.sum ?? 0);
    const purchasesCount = Number(purchasesRaw?.count ?? 0);

    const expensesRaw = await this.expenseRepo
      .createQueryBuilder('e')
      .select('COALESCE(SUM(e.amount), 0)', 'sum')
      .addSelect('COUNT(e.id)', 'count')
      .where('e.expenseDate >= :from', { from: fromDate })
      .andWhere('e.expenseDate <= :to', { to: toDate })
      .getRawOne<{ sum: string; count: string }>();
    const expensesSum = Number(expensesRaw?.sum ?? 0);
    const expensesCount = Number(expensesRaw?.count ?? 0);

    return {
      from,
      to,
      sales: { total: salesSum, count: salesCount },
      purchases: { total: purchasesSum, count: purchasesCount },
      expenses: { total: expensesSum, count: expensesCount },
    };
  }

  async getIncomeExpense(from: string, to: string): Promise<IncomeExpenseDto> {
    const fromDate = new Date(from);
    const toDate = new Date(to);
    toDate.setHours(23, 59, 59, 999);

    const salesRaw = await this.saleRepo
      .createQueryBuilder('s')
      .select('COALESCE(SUM(s.grandTotal), 0)', 'sum')
      .where('s.saleDate >= :from', { from: fromDate })
      .andWhere('s.saleDate <= :to', { to: toDate })
      .getRawOne<{ sum: string }>();
    const sales = Number(salesRaw?.sum ?? 0);

    const custPayRaw = await this.customerPaymentRepo
      .createQueryBuilder('cp')
      .select('COALESCE(SUM(cp.amount), 0)', 'sum')
      .where('cp.paymentDate >= :from', { from: fromDate })
      .andWhere('cp.paymentDate <= :to', { to: toDate })
      .getRawOne<{ sum: string }>();
    const customerPayments = Number(custPayRaw?.sum ?? 0);

    const purchRaw = await this.purchaseRepo
      .createQueryBuilder('p')
      .select('COALESCE(SUM(p.grandTotal), 0)', 'sum')
      .where('p.purchaseDate >= :from', { from: fromDate })
      .andWhere('p.purchaseDate <= :to', { to: toDate })
      .getRawOne<{ sum: string }>();
    const purchases = Number(purchRaw?.sum ?? 0);

    const expRaw = await this.expenseRepo
      .createQueryBuilder('e')
      .select('COALESCE(SUM(e.amount), 0)', 'sum')
      .where('e.expenseDate >= :from', { from: fromDate })
      .andWhere('e.expenseDate <= :to', { to: toDate })
      .getRawOne<{ sum: string }>();
    const expenses = Number(expRaw?.sum ?? 0);

    const suppPayRaw = await this.supplierPaymentRepo
      .createQueryBuilder('sp')
      .select('COALESCE(SUM(sp.amount), 0)', 'sum')
      .where('sp.paymentDate >= :from', { from: fromDate })
      .andWhere('sp.paymentDate <= :to', { to: toDate })
      .getRawOne<{ sum: string }>();
    const supplierPayments = Number(suppPayRaw?.sum ?? 0);

    // Toplam gelir = tahsilat (müşteri ödemeleri). Satış ayrı gösterilir; satış + tahsilat toplamı çift sayım yapar.
    const incomeTotal = customerPayments;
    const expenseTotal = purchases + expenses + supplierPayments;
    return {
      from,
      to,
      income: { sales, customerPayments, total: incomeTotal },
      expense: { purchases, expenses, supplierPayments, total: expenseTotal },
      net: incomeTotal - expenseTotal,
    };
  }

  async getProductSalesReport(from: string, to: string): Promise<{ from: string; to: string; rows: Array<{ productId: string; productName: string; quantity: number; total: number }> }> {
    const fromDate = new Date(from);
    const toDate = new Date(to);
    toDate.setHours(23, 59, 59, 999);
    const rows = await this.saleRepo.manager
      .createQueryBuilder()
      .select('si.productId', 'productId')
      .addSelect('p.name', 'productName')
      .addSelect('SUM(si.quantity)', 'quantity')
      .addSelect('SUM(si.lineTotal)', 'total')
      .from('sale_items', 'si')
      .innerJoin('sales', 's', 's.id = si.saleId')
      .innerJoin('products', 'p', 'p.id = si.productId')
      .where('s.saleDate >= :from', { from: fromDate })
      .andWhere('s.saleDate <= :to', { to: toDate })
      .groupBy('si.productId')
      .addGroupBy('p.name')
      .orderBy('SUM(si.lineTotal)', 'DESC')
      .getRawMany<{ productId: string; productName: string; quantity: string; total: string }>();
    return {
      from,
      to,
      rows: rows.map((r) => ({ productId: r.productId, productName: r.productName ?? '', quantity: Number(r.quantity ?? 0), total: Number(r.total ?? 0) })),
    };
  }

  async getCustomerSalesReport(from: string, to: string): Promise<{ from: string; to: string; rows: Array<{ customerId: string; customerName: string; count: number; total: number }> }> {
    const fromDate = new Date(from);
    const toDate = new Date(to);
    toDate.setHours(23, 59, 59, 999);
    const rows = await this.saleRepo
      .createQueryBuilder('s')
      .select('s.customerId', 'customerId')
      .addSelect('c.name', 'customerName')
      .addSelect('COUNT(s.id)', 'count')
      .addSelect('COALESCE(SUM(s.grandTotal), 0)', 'total')
      .innerJoin('customers', 'c', 'c.id = s.customerId')
      .where('s.saleDate >= :from', { from: fromDate })
      .andWhere('s.saleDate <= :to', { to: toDate })
      .groupBy('s.customerId')
      .addGroupBy('c.name')
      .orderBy('SUM(s.grandTotal)', 'DESC')
      .getRawMany<{ customerId: string; customerName: string; count: string; total: string }>();
    return {
      from,
      to,
      rows: rows.map((r) => ({ customerId: r.customerId, customerName: r.customerName ?? '', count: Number(r.count ?? 0), total: Number(r.total ?? 0) })),
    };
  }
}
