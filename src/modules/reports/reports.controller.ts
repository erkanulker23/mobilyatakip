import { Controller, Get, Query } from '@nestjs/common';
import { ReportsService } from './reports.service';

@Controller('reports')
export class ReportsController {
  constructor(private reports: ReportsService) {}

  @Get('summary')
  summary(
    @Query('from') from: string,
    @Query('to') to: string,
  ) {
    const fromDate = from || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
    const toDate = to || new Date().toISOString().slice(0, 10);
    return this.reports.getSummary(fromDate, toDate);
  }

  @Get('income-expense')
  incomeExpense(
    @Query('from') from: string,
    @Query('to') to: string,
  ) {
    const fromDate = from || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
    const toDate = to || new Date().toISOString().slice(0, 10);
    return this.reports.getIncomeExpense(fromDate, toDate);
  }

  @Get('product-sales')
  productSales(
    @Query('from') from: string,
    @Query('to') to: string,
  ) {
    const fromDate = from || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
    const toDate = to || new Date().toISOString().slice(0, 10);
    return this.reports.getProductSalesReport(fromDate, toDate);
  }

  @Get('customer-sales')
  customerSales(
    @Query('from') from: string,
    @Query('to') to: string,
  ) {
    const fromDate = from || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);
    const toDate = to || new Date().toISOString().slice(0, 10);
    return this.reports.getCustomerSalesReport(fromDate, toDate);
  }
}
