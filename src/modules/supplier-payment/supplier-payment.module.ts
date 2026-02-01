import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { SupplierPayment } from '../../entities/supplier-payment.entity';
import { Supplier } from '../../entities/supplier.entity';
import { SupplierPaymentService } from './supplier-payment.service';
import { SupplierPaymentController } from './supplier-payment.controller';
import { KasaHareketModule } from '../kasa-hareket/kasa-hareket.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([SupplierPayment, Supplier]),
    KasaHareketModule,
  ],
  providers: [SupplierPaymentService],
  controllers: [SupplierPaymentController],
  exports: [SupplierPaymentService],
})
export class SupplierPaymentModule {}
