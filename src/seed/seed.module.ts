import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { User } from '../entities/user.entity';
import { Company } from '../entities/company.entity';
import { Customer } from '../entities/customer.entity';
import { Supplier } from '../entities/supplier.entity';
import { Warehouse } from '../entities/warehouse.entity';
import { Personnel } from '../entities/personnel.entity';
import { Product } from '../entities/product.entity';
import { Kasa } from '../entities/kasa.entity';
import { Expense } from '../entities/expense.entity';
import { ExpenseCategory } from '../entities/expense-category.entity';
import { SuperadminSeedService } from './superadmin.seed';
import { ExpenseCategorySeedService } from './expense-category.seed';
import { DemoSeedService } from './demo.seed';
import { SeedController } from './seed.controller';
import { PurchaseModule } from '../modules/purchase/purchase.module';
import { SaleModule } from '../modules/sale/sale.module';
import { QuoteModule } from '../modules/quote/quote.module';
import { ServiceTicketModule } from '../modules/service-ticket/service-ticket.module';
import { SupplierPaymentModule } from '../modules/supplier-payment/supplier-payment.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([
      User,
      ExpenseCategory,
      Company,
      Customer,
      Supplier,
      Warehouse,
      Personnel,
      Product,
      Kasa,
      Expense,
    ]),
    PurchaseModule,
    SaleModule,
    QuoteModule,
    ServiceTicketModule,
    SupplierPaymentModule,
  ],
  controllers: [SeedController],
  providers: [SuperadminSeedService, ExpenseCategorySeedService, DemoSeedService],
})
export class SeedModule {}
