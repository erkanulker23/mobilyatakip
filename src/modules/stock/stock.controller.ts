import { Controller, Get, Post, Body, Param, Query } from '@nestjs/common';
import { StockService } from './stock.service';

@Controller('stock')
export class StockController {
  constructor(private stock: StockService) {}

  @Get('warehouse/:warehouseId')
  byWarehouse(@Param('warehouseId') warehouseId: string) {
    return this.stock.getByWarehouse(warehouseId);
  }

  @Get('product/:productId')
  byProduct(@Param('productId') productId: string) {
    return this.stock.getByProduct(productId);
  }

  @Get('low')
  lowStock(@Query('warehouseId') warehouseId?: string) {
    return this.stock.getLowStock(warehouseId);
  }

  @Get('movements/:productId')
  movements(@Param('productId') productId: string, @Query('warehouseId') warehouseId?: string, @Query('limit') limit?: string) {
    return this.stock.getMovements(productId, warehouseId, limit ? parseInt(limit, 10) : 50);
  }

  @Post('movement')
  movement(
    @Body()
    body: {
      productId: string;
      warehouseId: string;
      type: 'giris' | 'cikis' | 'transfer' | 'düzeltme';
      quantity: number;
      refType?: string;
      refId?: string;
      userId?: string;
      description?: string;
    },
  ) {
    return this.stock.movement(
      body.productId,
      body.warehouseId,
      body.type,
      body.quantity,
      {
        refType: body.refType as any,
        refId: body.refId,
        userId: body.userId,
        description: body.description,
      },
    );
  }
}
