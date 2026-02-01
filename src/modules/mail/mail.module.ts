import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { MailLog } from '../../entities/mail-log.entity';
import { MailService } from './mail.service';
import { MailController } from './mail.controller';

@Module({
  imports: [TypeOrmModule.forFeature([MailLog])],
  providers: [MailService],
  controllers: [MailController],
  exports: [MailService],
})
export class MailModule {}
