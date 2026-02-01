import { Controller, Get, Post, Put, Delete, Body, Param, Query, Res, StreamableFile } from '@nestjs/common';
import { Response } from 'express';
import { QuoteService, CreateQuoteDto } from './quote.service';
import { QuotePdfService } from './quote-pdf.service';
import { QuoteStatus } from '../../common/enums/quote-status.enum';

@Controller('quotes')
export class QuoteController {
  constructor(
    private quote: QuoteService,
    private quotePdf: QuotePdfService,
  ) {}

  @Get()
  list(
    @Query('customerId') customerId?: string,
    @Query('status') status?: QuoteStatus,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.quote.findAll({
      customerId,
      status,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.quote.findOne(id);
  }

  @Post()
  create(@Body() body: CreateQuoteDto) {
    return this.quote.create(body);
  }

  @Put(':id/status')
  updateStatus(@Param('id') id: string, @Body() body: { status: QuoteStatus }) {
    return this.quote.updateStatus(id, body.status);
  }

  @Put(':id/revision')
  newRevision(@Param('id') id: string, @Body() body: Partial<CreateQuoteDto>) {
    return this.quote.newRevision(id, body);
  }

  @Post(':id/convert-to-sale')
  convertToSale(@Param('id') id: string, @Body() body: { warehouseId: string }) {
    return this.quote.convertToSale(id, body.warehouseId);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.quote.remove(id);
  }

  @Get(':id/pdf')
  async getPdf(@Param('id') id: string, @Res({ passthrough: true }) res: Response) {
    const quote = await this.quote.findOne(id);
    const buffer = await this.quotePdf.generate(quote);
    res.set({
      'Content-Type': 'application/pdf',
      'Content-Disposition': `attachment; filename="${quote.quoteNumber}_v${quote.revision}.pdf"`,
      'Content-Length': buffer.length,
    });
    return new StreamableFile(buffer);
  }

  @Post(':id/pdf/save')
  async savePdf(@Param('id') id: string) {
    const quote = await this.quote.findOne(id);
    const filepath = await this.quotePdf.saveToFile(quote);
    return { filepath };
  }
}
