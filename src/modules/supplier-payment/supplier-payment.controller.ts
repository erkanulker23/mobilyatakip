import { Controller, Get, Post, Body, Param } from '@nestjs/common';
import { SupplierPaymentService } from './supplier-payment.service';

@Controller('supplier-payments')
export class SupplierPaymentController {
  constructor(private service: SupplierPaymentService) {}

  @Get('supplier/:supplierId')
  bySupplier(@Param('supplierId') supplierId: string) {
    return this.service.findBySupplier(supplierId);
  }

  @Get('supplier/:supplierId/balance')
  balance(@Param('supplierId') supplierId: string) {
    return this.service.getSupplierBalance(supplierId);
  }

  @Get('balances')
  allBalances() {
    return this.service.getAllSuppliersWithBalance();
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.service.create(body as any);
  }
}
