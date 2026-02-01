import { Controller, Get, Post, Put, Delete, Body, Param, Query } from '@nestjs/common';
import { CustomerService } from './customer.service';

@Controller('customers')
export class CustomerController {
  constructor(private customer: CustomerService) {}

  @Get()
  list(
    @Query('active') active?: string,
    @Query('search') search?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
    @Query('withBalance') withBalance?: string,
  ) {
    return this.customer.findAll({
      active: active === 'true' ? true : active === 'false' ? false : undefined,
      search,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
      withBalance: withBalance === 'true' || withBalance === '1',
    });
  }

  @Get('latest')
  getLatest(@Query('limit') limit?: string) {
    return this.customer.findLatest(limit ? parseInt(limit, 10) : 5);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.customer.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.customer.create(body as any);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.customer.update(id, body as any);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.customer.remove(id);
  }
}
