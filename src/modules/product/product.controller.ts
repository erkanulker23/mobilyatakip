import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseInterceptors, UploadedFile, BadRequestException, Res, StreamableFile } from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import { memoryStorage } from 'multer';
import { Response } from 'express';
import { ProductService } from './product.service';

@Controller('products')
export class ProductController {
  constructor(private product: ProductService) {}

  @Get('export')
  async export(
    @Res({ passthrough: true }) res: Response,
    @Query('includeExisting') includeExisting?: string,
    @Query('search') search?: string,
    @Query('supplierId') supplierId?: string,
    @Query('active') active?: string,
    @Query('limit') limit?: string,
  ) {
    const buffer = await this.product.exportToExcel({
      includeExisting: includeExisting !== 'false' && includeExisting !== '0',
      search,
      supplierId,
      active: active === 'true' ? true : active === 'false' ? false : undefined,
      limit: limit ? Number(limit) : undefined,
    });
    const filename = `urunler-${new Date().toISOString().slice(0, 10)}.xlsx`;
    res.set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.set('Content-Disposition', `attachment; filename="${filename}"`);
    res.set('Content-Length', String(buffer.length));
    return new StreamableFile(buffer);
  }

  @Get()
  list(
    @Query('search') search?: string,
    @Query('supplierId') supplierId?: string,
    @Query('active') active?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.product.findAll({
      search,
      supplierId,
      active: active === 'true' ? true : active === 'false' ? false : undefined,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Post('import')
  @UseInterceptors(FileInterceptor('file', { storage: memoryStorage() }))
  async import(
    @UploadedFile() file: Express.Multer.File & { buffer?: Buffer },
    @Body() body: { supplierId?: string },
  ) {
    const buffer = file?.buffer;
    if (!buffer || !file?.originalname) throw new BadRequestException('Dosya yükleyin');
    const name = file.originalname.toLowerCase();
    const supplierId = body?.supplierId || undefined;
    if (name.endsWith('.xlsx') || name.endsWith('.xls')) {
      return this.product.importFromExcel(buffer, supplierId);
    }
    if (name.endsWith('.xml')) {
      return this.product.importFromXml(buffer, supplierId);
    }
    throw new BadRequestException('Sadece .xlsx, .xls veya .xml dosyaları kabul edilir');
  }

  /** XML feed URL'den (RSS / Google Shopping g: yapısı) ürünleri çeker. Resimler ve tedarikçi dahil. */
  @Post('import-from-feed')
  async importFromFeed(@Body() body: { feedUrl: string; supplierId?: string }) {
    const feedUrl = body?.feedUrl?.trim();
    if (!feedUrl) throw new BadRequestException('feedUrl gerekli');
    try {
      new URL(feedUrl);
    } catch {
      throw new BadRequestException('Geçerli bir feed URL girin');
    }
    return this.product.importFromFeedUrl(feedUrl, body?.supplierId);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.product.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.product.create(body as any);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.product.update(id, body as any);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.product.remove(id);
  }

  @Post('bulk-delete')
  bulkDelete(@Body() body: { ids: string[] }) {
    const ids = Array.isArray(body?.ids) ? body.ids : [];
    return this.product.bulkDelete(ids);
  }
}
