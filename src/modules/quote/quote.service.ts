import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Quote } from '../../entities/quote.entity';
import { QuoteItem } from '../../entities/quote-item.entity';
import { QuoteRevision } from '../../entities/quote-revision.entity';
import { QuoteStatus } from '../../common/enums/quote-status.enum';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';
import { ProductService } from '../product/product.service';
import { SaleService } from '../sale/sale.service';

export interface QuoteItemInput {
  productId: string;
  unitPrice: number;
  quantity: number;
  lineDiscountPercent?: number;
  lineDiscountAmount?: number;
  kdvRate?: number;
}

export interface CreateQuoteDto {
  customerId: string;
  items: QuoteItemInput[];
  generalDiscountPercent?: number;
  generalDiscountAmount?: number;
  validUntil?: string;
  notes?: string;
  personnelId?: string;
  customerSource?: string;
}

@Injectable()
export class QuoteService {
  constructor(
    @InjectRepository(Quote)
    private quoteRepo: Repository<Quote>,
    @InjectRepository(QuoteItem)
    private itemRepo: Repository<QuoteItem>,
    @InjectRepository(QuoteRevision)
    private revisionRepo: Repository<QuoteRevision>,
    private productService: ProductService,
    private saleService: SaleService,
  ) {}

  private async nextQuoteNumber(): Promise<string> {
    const year = new Date().getFullYear();
    const last = await this.quoteRepo
      .createQueryBuilder('q')
      .where("q.quoteNumber LIKE :prefix", { prefix: `TKL-${year}-%` })
      .orderBy('q.quoteNumber', 'DESC')
      .getOne();
    const next = last ? parseInt(last.quoteNumber.split('-').pop() || '0', 10) + 1 : 1;
    return `TKL-${year}-${String(next).padStart(5, '0')}`;
  }

  private calcLineTotal(item: {
    unitPrice: number;
    quantity: number;
    lineDiscountPercent?: number;
    lineDiscountAmount?: number;
  }): number {
    const raw = Number(item.unitPrice) * item.quantity;
    const discP = Number(item.lineDiscountPercent || 0) / 100;
    const discA = Number(item.lineDiscountAmount || 0);
    return Math.max(0, raw - raw * discP - discA);
  }

  async create(dto: CreateQuoteDto): Promise<Quote> {
    const quoteNumber = await this.nextQuoteNumber();
    const quote = this.quoteRepo.create({
      quoteNumber,
      customerId: dto.customerId,
      status: QuoteStatus.TASLAK,
      generalDiscountPercent: dto.generalDiscountPercent ?? 0,
      generalDiscountAmount: dto.generalDiscountAmount ?? 0,
      validUntil: dto.validUntil ? new Date(dto.validUntil) : undefined,
      notes: dto.notes,
      revision: 1,
      personnelId: dto.personnelId ?? undefined,
      customerSource: dto.customerSource ?? undefined,
    });
    await this.quoteRepo.save(quote);
    let subtotal = 0;
    for (const row of dto.items) {
      const product = await this.productService.findOne(row.productId);
      const lineTotal = this.calcLineTotal(row);
      const kdvRate = Number(row.kdvRate ?? product.kdvRate ?? 18);
      const item = this.itemRepo.create({
        quoteId: quote.id,
        productId: row.productId,
        unitPrice: row.unitPrice,
        quantity: row.quantity,
        lineDiscountPercent: row.lineDiscountPercent ?? 0,
        lineDiscountAmount: row.lineDiscountAmount ?? 0,
        kdvRate,
        lineTotal,
      });
      await this.itemRepo.save(item);
      subtotal += lineTotal;
    }
    const generalDisc = (subtotal * (quote.generalDiscountPercent / 100)) + Number(quote.generalDiscountAmount);
    const afterDisc = Math.max(0, subtotal - generalDisc);
    const kdvTotal = await this.recalcKdvTotal(quote.id);
    const grandTotal = afterDisc + kdvTotal;
    quote.subtotal = subtotal;
    quote.kdvTotal = kdvTotal;
    quote.grandTotal = grandTotal;
    await this.quoteRepo.save(quote);
    await this.revisionRepo.save(
      this.revisionRepo.create({ quoteId: quote.id, version: 1, grandTotal: quote.grandTotal }),
    );
    return this.findOne(quote.id);
  }

  private async recalcKdvTotal(quoteId: string): Promise<number> {
    const items = await this.itemRepo.find({ where: { quoteId } });
    let total = 0;
    for (const i of items) {
      total += Number(i.lineTotal) * (Number(i.kdvRate) / 100);
    }
    return total;
  }

  async findAll(params?: {
    customerId?: string;
    status?: QuoteStatus;
    page?: number;
    limit?: number;
  }): Promise<PaginatedResult<Quote>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const where: Record<string, unknown> = {};
    if (params?.customerId) where.customerId = params.customerId;
    if (params?.status) where.status = params.status;
    const [data, total] = await this.quoteRepo.findAndCount({
      where,
      relations: ['customer', 'personnel', 'items', 'items.product'],
      order: { createdAt: 'DESC' },
      skip,
      take: limit,
    });
    return {
      data,
      total,
      page,
      limit,
      totalPages: Math.ceil(total / limit) || 1,
    };
  }

  async findOne(id: string): Promise<Quote> {
    const q = await this.quoteRepo.findOne({
      where: { id },
      relations: ['customer', 'personnel', 'items', 'items.product', 'revisions'],
    });
    if (!q) throw new Error('Teklif bulunamadı');
    return q;
  }

  async updateStatus(id: string, status: QuoteStatus): Promise<Quote> {
    const quote = await this.findOne(id);
    quote.status = status;
    await this.quoteRepo.save(quote);
    return this.findOne(id);
  }

  async newRevision(id: string, dto: Partial<CreateQuoteDto>): Promise<Quote> {
    const quote = await this.findOne(id);
    if (quote.status === QuoteStatus.SATISA_DONUSTU) throw new Error('Satışa dönüşmüş teklif revize edilemez');
    const newRev = quote.revision + 1;
    if (dto.items) {
      await this.itemRepo.delete({ quoteId: id });
      let subtotal = 0;
      for (const row of dto.items) {
        const product = await this.productService.findOne(row.productId);
        const lineTotal = this.calcLineTotal(row);
        const item = this.itemRepo.create({
          quoteId: id,
          productId: row.productId,
          unitPrice: row.unitPrice,
          quantity: row.quantity,
          lineDiscountPercent: row.lineDiscountPercent ?? 0,
          lineDiscountAmount: row.lineDiscountAmount ?? 0,
          kdvRate: row.kdvRate ?? product.kdvRate ?? 18,
          lineTotal,
        });
        await this.itemRepo.save(item);
        subtotal += lineTotal;
      }
      quote.generalDiscountPercent = dto.generalDiscountPercent ?? quote.generalDiscountPercent;
      quote.generalDiscountAmount = dto.generalDiscountAmount ?? quote.generalDiscountAmount;
      const generalDisc = (subtotal * (Number(quote.generalDiscountPercent) / 100)) + Number(quote.generalDiscountAmount);
      quote.subtotal = subtotal;
      quote.kdvTotal = await this.recalcKdvTotal(id);
      quote.grandTotal = Math.max(0, subtotal - generalDisc) + Number(quote.kdvTotal);
    }
    quote.revision = newRev;
    await this.quoteRepo.save(quote);
    await this.revisionRepo.save(
      this.revisionRepo.create({ quoteId: id, version: newRev, grandTotal: quote.grandTotal }),
    );
    return this.findOne(id);
  }

  async convertToSale(id: string, warehouseId: string): Promise<{ saleId: string }> {
    const quote = await this.findOne(id);
    if (quote.convertedSaleId) throw new Error('Bu teklif zaten satışa dönüştürülmüş');
    const allowed = [QuoteStatus.TASLAK, QuoteStatus.ONAYLANDI];
    if (!allowed.includes(quote.status)) throw new Error('Bu teklif satışa dönüştürülemez');
    const sale = await this.saleService.createFromQuote(quote.id, warehouseId);
    quote.status = QuoteStatus.SATISA_DONUSTU;
    quote.convertedSaleId = sale.id;
    await this.quoteRepo.save(quote);
    return { saleId: sale.id };
  }

  async remove(id: string): Promise<void> {
    const quote = await this.quoteRepo.findOne({ where: { id } });
    if (!quote) throw new Error('Teklif bulunamadı');
    if (quote.convertedSaleId) throw new Error('Satışa dönüşmüş teklif silinemez');
    await this.itemRepo.delete({ quoteId: id });
    await this.revisionRepo.delete({ quoteId: id });
    await this.quoteRepo.remove(quote);
  }
}
