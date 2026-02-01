import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Sale } from '../../entities/sale.entity';
import { SaleItem } from '../../entities/sale-item.entity';
import { Quote } from '../../entities/quote.entity';
import { QuoteItem } from '../../entities/quote-item.entity';
import { SaleService } from './sale.service';
import { SaleController } from './sale.controller';
import { SalePdfService } from './sale-pdf.service';
import { StockModule } from '../stock/stock.module';
import { CompanyModule } from '../company/company.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Sale, SaleItem, Quote, QuoteItem, require('../../entities/product.entity').Product, require('../../entities/stock-movement.entity').StockMovement]),
    StockModule,
    CompanyModule,
  ],
  providers: [SaleService, SalePdfService],
  controllers: [SaleController],
  exports: [SaleService],
})
export class SaleModule {}
