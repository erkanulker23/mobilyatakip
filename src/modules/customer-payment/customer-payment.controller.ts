import { Controller, Get, Post, Body, Param, Query } from '@nestjs/common';
import { CustomerPaymentService } from './customer-payment.service';

@Controller('customer-payments')
export class CustomerPaymentController {
  constructor(private service: CustomerPaymentService) {}

  @Get('with-debt')
  withDebt() {
    return this.service.getCustomersWithDebt();
  }

  @Get('latest')
  getLatest(@Query('limit') limit?: string) {
    return this.service.findLatest(limit ? parseInt(limit, 10) : 5);
  }

  @Get('customer/:customerId')
  byCustomer(@Param('customerId') customerId: string) {
    return this.service.findByCustomer(customerId);
  }

  @Get('customer/:customerId/balance')
  balance(@Param('customerId') customerId: string) {
    return this.service.getCustomerBalance(customerId);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.service.create(body as any);
  }
}
