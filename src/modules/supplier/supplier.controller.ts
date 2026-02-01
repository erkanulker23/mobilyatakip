import { Controller, Get, Post, Put, Delete, Body, Param, Query } from '@nestjs/common';
import { SupplierService } from './supplier.service';

@Controller('suppliers')
export class SupplierController {
  constructor(private supplier: SupplierService) {}

  @Get()
  list(
    @Query('active') active?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.supplier.findAllWithProducts({
      active: active === 'true' ? true : active === 'false' ? false : undefined,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Get(':id/stats')
  getStats(@Param('id') id: string) {
    return this.supplier.getSalesStats(id);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.supplier.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.supplier.create(body as any);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.supplier.update(id, body as any);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.supplier.remove(id);
  }
}
