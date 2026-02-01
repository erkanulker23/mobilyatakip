import { Controller, Get, Post, Put, Delete, Body, Param, Query, Res, StreamableFile } from '@nestjs/common';
import { Response } from 'express';
import { SaleService } from './sale.service';
import { SalePdfService } from './sale-pdf.service';

@Controller('sales')
export class SaleController {
  constructor(
    private readonly sale: SaleService,
    private readonly salePdf: SalePdfService,
  ) {}

  @Get()
  list(
    @Query('customerId') customerId?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.sale.findAll({
      customerId,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Get(':id/pdf')
  async getPdf(@Param('id') id: string, @Res({ passthrough: true }) res: Response) {
    const sale = await this.sale.findOne(id);
    const buffer = await this.salePdf.generate(sale);
    res.set({
      'Content-Type': 'application/pdf',
      'Content-Disposition': `attachment; filename="${sale.saleNumber}.pdf"`,
      'Content-Length': buffer.length,
    });
    return new StreamableFile(buffer);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.sale.findOne(id);
  }

  @Post()
  create(@Body() body: { customerId: string; warehouseId: string; dueDate?: string; notes?: string; items: Array<{ productId: string; quantity: number; unitPrice?: number; kdvRate?: number }> }) {
    return this.sale.create(body);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: { dueDate?: string; notes?: string }) {
    return this.sale.update(id, body);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.sale.remove(id);
  }

  @Post('from-quote')
  fromQuote(@Body() body: { quoteId: string; warehouseId: string }) {
    return this.sale.createFromQuote(body.quoteId, body.warehouseId);
  }
}
