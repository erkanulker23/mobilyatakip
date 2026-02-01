import { Controller, Get, Post, Body, Param, Res } from '@nestjs/common';
import { Response } from 'express';
import { SupplierStatementService } from './supplier-statement.service';
import { SupplierStatementPdfService } from './supplier-statement-pdf.service';
import { StreamableFile } from '@nestjs/common';

@Controller('supplier-statements')
export class SupplierStatementController {
  constructor(
    private service: SupplierStatementService,
    private pdfService: SupplierStatementPdfService,
  ) {}

  @Get('supplier/:supplierId')
  bySupplier(@Param('supplierId') supplierId: string) {
    return this.service.findBySupplier(supplierId);
  }

  @Post('generate')
  generate(@Body() body: { supplierId: string; startDate: string; endDate: string }) {
    return this.service.generate(
      body.supplierId,
      new Date(body.startDate),
      new Date(body.endDate),
    );
  }

  @Get(':id/pdf')
  async getPdf(@Param('id') id: string, @Res({ passthrough: true }) res: Response) {
    const statement = await this.service.findOne(id);
    const buffer = await this.pdfService.generate(statement);
    res.set({
      'Content-Type': 'application/pdf',
      'Content-Disposition': `attachment; filename="mutabakat-${id}.pdf"`,
    });
    return new StreamableFile(buffer);
  }

  @Post(':id/approve')
  approve(@Param('id') id: string) {
    return this.service.approve(id);
  }
}
