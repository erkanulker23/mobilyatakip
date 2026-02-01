import { Controller, Get, Post, Put, Body, Param, Query } from '@nestjs/common';
import { WarehouseService } from './warehouse.service';

@Controller('warehouses')
export class WarehouseController {
  constructor(private warehouse: WarehouseService) {}

  @Get()
  list(@Query('active') active?: string) {
    return this.warehouse.findAll(active === 'true' ? true : active === 'false' ? false : undefined);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.warehouse.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.warehouse.create(body as any);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.warehouse.update(id, body as any);
  }
}
