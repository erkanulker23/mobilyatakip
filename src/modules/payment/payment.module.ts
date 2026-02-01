import { Module } from '@nestjs/common';
import { PaymentController } from './payment.controller';
import { PaymentService } from './payment.service';
import { CompanyModule } from '../company/company.module';
import { AuthModule } from '../auth/auth.module';

@Module({
  imports: [CompanyModule, AuthModule],
  controllers: [PaymentController],
  providers: [PaymentService],
})
export class PaymentModule {}
