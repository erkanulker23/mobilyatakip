import { Injectable, BadRequestException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Sale } from '../../entities/sale.entity';
import { SaleItem } from '../../entities/sale-item.entity';
import { Quote } from '../../entities/quote.entity';
import { Product } from '../../entities/product.entity';
import { StockMovement } from '../../entities/stock-movement.entity';
import { StockService } from '../stock/stock.service';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';

export interface CreateSaleDto {
  customerId: string;
  warehouseId: string;
  dueDate?: string;
  notes?: string;
  items: Array<{ productId: string; quantity: number; unitPrice?: number; kdvRate?: number }>;
}

@Injectable()
export class SaleService {
  constructor(
    @InjectRepository(Sale)
    private saleRepo: Repository<Sale>,
    @InjectRepository(SaleItem)
    private itemRepo: Repository<SaleItem>,
    @InjectRepository(Quote)
    private quoteRepo: Repository<Quote>,
    @InjectRepository(Product)
    private productRepo: Repository<Product>,
    @InjectRepository(StockMovement)
    private movementRepo: Repository<StockMovement>,
    private stockService: StockService,
  ) {}

  private async nextSaleNumber(): Promise<string> {
    const year = new Date().getFullYear();
    const last = await this.saleRepo
      .createQueryBuilder('s')
      .where('s.saleNumber LIKE :prefix', { prefix: `SAT-${year}-%` })
      .orderBy('s.saleNumber', 'DESC')
      .getOne();
    const next = last ? parseInt(last.saleNumber.split('-').pop() || '0', 10) + 1 : 1;
    return `SAT-${year}-${String(next).padStart(5, '0')}`;
  }

  async createFromQuote(quoteId: string, warehouseId: string): Promise<Sale> {
    const quote = await this.quoteRepo.findOne({
      where: { id: quoteId },
      relations: ['customer', 'items', 'items.product'],
    });
    if (!quote) throw new BadRequestException('Teklif bulunamadı');
    if (!warehouseId?.trim()) throw new BadRequestException('Depo seçimi zorunludur');
    for (const qi of quote.items) {
      const stock = await this.stockService.getStock(qi.productId, warehouseId);
      const available = Number(stock.quantity) - Number(stock.reservedQuantity ?? 0);
      if (available < qi.quantity) {
        const name = (qi as { product?: { name?: string } }).product?.name ?? qi.productId;
        throw new BadRequestException(
          `Yetersiz stok: ${name} - Depoda ${available} adet, ${qi.quantity} adet satılamaz`,
        );
      }
    }
    const saleNumber = await this.nextSaleNumber();
    const sale = this.saleRepo.create({
      saleNumber,
      customerId: quote.customerId,
      quoteId: quote.id,
      saleDate: new Date(),
      dueDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000),
      subtotal: quote.subtotal,
      kdvTotal: quote.kdvTotal,
      grandTotal: quote.grandTotal,
      paidAmount: 0,
    });
    await this.saleRepo.save(sale);
    for (const qi of quote.items) {
      const lineNet = Number(qi.lineTotal);
      const lineKdv = lineNet * (Number(qi.kdvRate) / 100);
      const item = this.itemRepo.create({
        saleId: sale.id,
        productId: qi.productId,
        unitPrice: qi.unitPrice,
        quantity: qi.quantity,
        kdvRate: qi.kdvRate,
        lineTotal: lineNet + lineKdv,
      });
      await this.itemRepo.save(item);
      await this.stockService.movement(
        qi.productId,
        warehouseId,
        'cikis',
        qi.quantity,
        { refType: 'satis', refId: sale.id, description: `Satış ${saleNumber}` },
      );
    }
    return this.findOne(sale.id);
  }

  async create(dto: CreateSaleDto): Promise<Sale> {
    if (!dto.items?.length) {
      throw new BadRequestException('En az bir satış kalemi gerekli');
    }
    if (!dto.customerId?.trim()) throw new BadRequestException('Müşteri seçimi zorunludur');
    if (!dto.warehouseId?.trim()) throw new BadRequestException('Depo seçimi zorunludur');
    for (const row of dto.items) {
      if (!row.productId?.trim()) throw new BadRequestException('Ürün seçimi zorunludur');
      const qty = row.quantity ?? 1;
      if (qty < 1) throw new BadRequestException('Miktar 1 veya üzeri olmalıdır');
      const product = await this.productRepo.findOne({ where: { id: row.productId } });
      if (!product) throw new BadRequestException(`Ürün bulunamadı: ${row.productId}`);
      const stock = await this.stockService.getStock(row.productId, dto.warehouseId);
      const available = Number(stock.quantity) - Number(stock.reservedQuantity ?? 0);
      if (available < qty) {
        throw new BadRequestException(
          `Yetersiz stok: ${product.name} - Depoda ${available} adet, ${qty} adet satılamaz`,
        );
      }
    }

    const saleNumber = await this.nextSaleNumber();
    const dueDate = dto.dueDate ? new Date(dto.dueDate) : new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);
    const sale = this.saleRepo.create({
      saleNumber,
      customerId: dto.customerId,
      saleDate: new Date(),
      dueDate,
      subtotal: 0,
      kdvTotal: 0,
      grandTotal: 0,
      paidAmount: 0,
      notes: dto.notes,
    });
    await this.saleRepo.save(sale);
    let subtotal = 0;
    let kdvTotal = 0;
    for (const row of dto.items) {
      const product = await this.productRepo.findOne({ where: { id: row.productId } });
      if (!product) throw new Error(`Ürün bulunamadı: ${row.productId}`);
      const unitPrice = Number(row.unitPrice ?? product.unitPrice ?? 0);
      const quantity = row.quantity || 1;
      const kdvRate = Number(row.kdvRate ?? product.kdvRate ?? 18);
      const lineNet = unitPrice * quantity;
      const lineKdv = lineNet * (kdvRate / 100);
      const lineTotal = lineNet + lineKdv;
      const item = this.itemRepo.create({
        saleId: sale.id,
        productId: row.productId,
        unitPrice,
        quantity,
        kdvRate,
        lineTotal,
      });
      await this.itemRepo.save(item);
      subtotal += lineNet;
      kdvTotal += lineKdv;
      await this.stockService.movement(
        row.productId,
        dto.warehouseId,
        'cikis',
        quantity,
        { refType: 'satis', refId: sale.id, description: `Satış ${saleNumber}` },
      );
    }
    sale.subtotal = subtotal;
    sale.kdvTotal = kdvTotal;
    sale.grandTotal = subtotal + kdvTotal;
    await this.saleRepo.save(sale);
    return this.findOne(sale.id);
  }

  async findAll(params?: {
    customerId?: string;
    page?: number;
    limit?: number;
  }): Promise<PaginatedResult<Sale>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const where: Record<string, string> = {};
    if (params?.customerId) where.customerId = params.customerId;
    const [data, total] = await this.saleRepo.findAndCount({
      where,
      relations: ['customer', 'items', 'items.product'],
      order: { saleDate: 'DESC' },
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

  async findOne(id: string): Promise<Sale> {
    const s = await this.saleRepo.findOne({
      where: { id },
      relations: ['customer', 'quote', 'items', 'items.product'],
    });
    if (!s) throw new Error('Satış bulunamadı');
    return s;
  }

  async update(id: string, data: { dueDate?: string; notes?: string }): Promise<Sale> {
    const sale = await this.findOne(id);
    if (data.dueDate !== undefined) (sale as { dueDate: Date | null }).dueDate = data.dueDate ? new Date(data.dueDate) : null;
    if (data.notes !== undefined) sale.notes = data.notes;
    await this.saleRepo.save(sale);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    const sale = await this.findOne(id);
    const movements = await this.movementRepo.find({
      where: { refType: 'satis', refId: id },
    });
    for (const m of movements) {
      const qty = Math.abs(Number(m.quantity));
      await this.stockService.movement(
        m.productId,
        m.warehouseId,
        'giris',
        qty,
        { refType: 'manuel', description: 'Satış iptal' },
      );
    }
    await this.itemRepo.delete({ saleId: id });
    await this.saleRepo.delete(id);
  }
}
