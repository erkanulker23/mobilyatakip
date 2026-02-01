import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { SupplierStatement } from '../../entities/supplier-statement.entity';
import { Purchase } from '../../entities/purchase.entity';
import { SupplierPayment } from '../../entities/supplier-payment.entity';
import { SupplierStatementService } from './supplier-statement.service';
import { SupplierStatementController } from './supplier-statement.controller';
import { SupplierStatementPdfService } from './supplier-statement-pdf.service';
import { SupplierPaymentModule } from '../supplier-payment/supplier-payment.module';
import { CompanyModule } from '../company/company.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([SupplierStatement, Purchase, SupplierPayment]),
    SupplierPaymentModule,
    CompanyModule,
  ],
  providers: [SupplierStatementService, SupplierStatementPdfService],
  controllers: [SupplierStatementController],
  exports: [SupplierStatementService],
})
export class SupplierStatementModule {}
