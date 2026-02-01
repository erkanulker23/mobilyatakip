import { Module } from '@nestjs/common';
import { SmsController } from './sms.controller';
import { SmsService } from './sms.service';
import { CompanyModule } from '../company/company.module';
import { AuthModule } from '../auth/auth.module';

@Module({
  imports: [CompanyModule, AuthModule],
  controllers: [SmsController],
  providers: [SmsService],
  exports: [SmsService],
})
export class SmsModule {}
