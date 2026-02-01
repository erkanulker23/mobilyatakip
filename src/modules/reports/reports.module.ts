import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Sale } from '../../entities/sale.entity';
import { Purchase } from '../../entities/purchase.entity';
import { Expense } from '../../entities/expense.entity';
import { CustomerPayment } from '../../entities/customer-payment.entity';
import { SupplierPayment } from '../../entities/supplier-payment.entity';
import { ReportsService } from './reports.service';
import { ReportsController } from './reports.controller';

@Module({
  imports: [
    TypeOrmModule.forFeature([Sale, Purchase, Expense, CustomerPayment, SupplierPayment]),
  ],
  providers: [ReportsService],
  controllers: [ReportsController],
  exports: [ReportsService],
})
export class ReportsModule {}
