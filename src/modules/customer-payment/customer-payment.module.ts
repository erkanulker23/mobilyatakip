import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { CustomerPayment } from '../../entities/customer-payment.entity';
import { Sale } from '../../entities/sale.entity';
import { CustomerPaymentService } from './customer-payment.service';
import { CustomerPaymentController } from './customer-payment.controller';
import { KasaHareketModule } from '../kasa-hareket/kasa-hareket.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([CustomerPayment, Sale]),
    KasaHareketModule,
  ],
  providers: [CustomerPaymentService],
  controllers: [CustomerPaymentController],
  exports: [CustomerPaymentService],
})
export class CustomerPaymentModule {}
