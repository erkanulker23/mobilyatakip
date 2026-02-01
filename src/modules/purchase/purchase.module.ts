import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Purchase } from '../../entities/purchase.entity';
import { PurchaseItem } from '../../entities/purchase-item.entity';
import { PurchaseService } from './purchase.service';
import { PurchaseController } from './purchase.controller';
import { ProductModule } from '../product/product.module';
import { StockModule } from '../stock/stock.module';
import { KasaHareketModule } from '../kasa-hareket/kasa-hareket.module';
import { AuthModule } from '../auth/auth.module';

@Module({
  imports: [
    TypeOrmModule.forFeature([Purchase, PurchaseItem, require('../../entities/stock-movement.entity').StockMovement]),
    ProductModule,
    StockModule,
    KasaHareketModule,
    AuthModule,
  ],
  providers: [PurchaseService],
  controllers: [PurchaseController],
  exports: [PurchaseService],
})
export class PurchaseModule {}
