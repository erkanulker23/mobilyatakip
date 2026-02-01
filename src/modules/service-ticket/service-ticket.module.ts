import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ServiceTicket } from '../../entities/service-ticket.entity';
import { ServiceTicketDetail } from '../../entities/service-ticket-detail.entity';
import { ServicePart } from '../../entities/service-part.entity';
import { ServiceTicketService } from './service-ticket.service';
import { ServiceTicketController } from './service-ticket.controller';
import { ServiceTicketPdfService } from './service-ticket-pdf.service';
import { StockModule } from '../stock/stock.module';
import { CompanyModule } from '../company/company.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([ServiceTicket, ServiceTicketDetail, ServicePart]),
    StockModule,
    CompanyModule,
  ],
  providers: [ServiceTicketService, ServiceTicketPdfService],
  controllers: [ServiceTicketController],
  exports: [ServiceTicketService],
})
export class ServiceTicketModule {}
