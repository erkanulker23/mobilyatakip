import { Controller, Get, Post, Put, Body, Param, Query, Res } from '@nestjs/common';
import { Response } from 'express';
import { StreamableFile } from '@nestjs/common';
import { ServiceTicketService } from './service-ticket.service';
import { ServiceTicketPdfService } from './service-ticket-pdf.service';
import { ServiceTicketStatus } from '../../common/enums/service-ticket-status.enum';

@Controller('service-tickets')
export class ServiceTicketController {
  constructor(
    private service: ServiceTicketService,
    private pdfService: ServiceTicketPdfService,
  ) {}

  @Get()
  list(
    @Query('customerId') customerId?: string,
    @Query('saleId') saleId?: string,
    @Query('status') status?: ServiceTicketStatus,
    @Query('openedAtFrom') openedAtFrom?: string,
    @Query('openedAtTo') openedAtTo?: string,
    @Query('search') search?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.service.findAll({
      customerId,
      saleId,
      status,
      openedAtFrom,
      openedAtTo,
      search,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Get('stats/open-count')
  openCount() {
    return this.service.getOpenCount();
  }

  @Get('stats/avg-resolution-days')
  avgResolution() {
    return this.service.getAverageResolutionTime();
  }

  @Get(':id/pdf')
  async getPdf(@Param('id') id: string, @Res({ passthrough: true }) res: Response) {
    const ticket = await this.service.findOne(id);
    const buffer = await this.pdfService.generate(ticket as any);
    res.set({
      'Content-Type': 'application/pdf',
      'Content-Disposition': `attachment; filename="${ticket.ticketNumber}.pdf"`,
      'Content-Length': buffer.length,
    });
    return new StreamableFile(buffer);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.service.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.service.create(body as any);
  }

  @Put(':id/status')
  updateStatus(@Param('id') id: string, @Body() body: { status: ServiceTicketStatus }) {
    return this.service.updateStatus(id, body.status);
  }

  @Post(':id/details')
  addDetail(
    @Param('id') id: string,
    @Body() body: { userId: string; action: string; notes?: string; parts?: { productId: string; quantity: number }[]; warehouseId?: string; images?: string[] },
  ) {
    return this.service.addDetail(id, body.userId, body.action, body.notes, body.parts, body.warehouseId, body.images);
  }
}
