import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Quote } from '../../entities/quote.entity';
import { QuoteItem } from '../../entities/quote-item.entity';
import { QuoteRevision } from '../../entities/quote-revision.entity';
import { QuoteService } from './quote.service';
import { QuoteController } from './quote.controller';
import { QuotePdfService } from './quote-pdf.service';
import { CompanyModule } from '../company/company.module';
import { ProductModule } from '../product/product.module';
import { SaleModule } from '../sale/sale.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Quote, QuoteItem, QuoteRevision]),
    CompanyModule,
    ProductModule,
    SaleModule,
  ],
  providers: [QuoteService, QuotePdfService],
  controllers: [QuoteController],
  exports: [QuoteService, QuotePdfService],
})
export class QuoteModule {}
