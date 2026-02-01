import { Controller, Get, Put, Body } from '@nestjs/common';
import { CompanyService } from './company.service';

@Controller('company')
export class CompanyController {
  constructor(private company: CompanyService) {}

  @Get()
  get() {
    return this.company.findOne();
  }

  @Put()
  upsert(@Body() body: Record<string, unknown>) {
    return this.company.upsert(body as any);
  }
}
