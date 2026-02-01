import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Stock } from '../../entities/stock.entity';
import { StockMovement } from '../../entities/stock-movement.entity';
import { StockService } from './stock.service';
import { StockController } from './stock.controller';

@Module({
  imports: [TypeOrmModule.forFeature([Stock, StockMovement])],
  providers: [StockService],
  controllers: [StockController],
  exports: [StockService],
})
export class StockModule {}
