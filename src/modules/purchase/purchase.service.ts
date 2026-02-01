import { Injectable, Logger, BadRequestException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Purchase } from '../../entities/purchase.entity';
import { PurchaseItem } from '../../entities/purchase-item.entity';
import { StockMovement } from '../../entities/stock-movement.entity';
import { ProductService } from '../product/product.service';
import { StockService } from '../stock/stock.service';
import { KasaHareketService } from '../kasa-hareket/kasa-hareket.service';
import { PaymentType } from '../../common/enums/payment-type.enum';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';

export interface PurchaseItemInput {
  productId: string;
  unitPrice: number;
  quantity: number;
  kdvRate?: number;
}

export interface CreatePurchaseDto {
  supplierId: string;
  purchaseDate: string;
  dueDate?: string;
  items: PurchaseItemInput[];
  isReturn?: boolean;
  notes?: string;
  /** Nakit alışta kasadan çıkış için: ödeme türü ve kasa seçimi */
  paymentType?: PaymentType;
  kasaId?: string;
}

const LARGE_PURCHASE_THRESHOLD = 50_000;

@Injectable()
export class PurchaseService {
  private readonly logger = new Logger(PurchaseService.name);

  constructor(
    @InjectRepository(Purchase)
    private purchaseRepo: Repository<Purchase>,
    @InjectRepository(PurchaseItem)
    private itemRepo: Repository<PurchaseItem>,
    @InjectRepository(StockMovement)
    private movementRepo: Repository<StockMovement>,
    private productService: ProductService,
    private stockService: StockService,
    private kasaHareketService: KasaHareketService,
  ) {}

  private async nextPurchaseNumber(): Promise<string> {
    const year = new Date().getFullYear();
    const last = await this.purchaseRepo
      .createQueryBuilder('p')
      .where('p.purchaseNumber LIKE :prefix', { prefix: `ALS-${year}-%` })
      .orderBy('p.purchaseNumber', 'DESC')
      .getOne();
    const next = last ? parseInt(last.purchaseNumber.split('-').pop() || '0', 10) + 1 : 1;
    return `ALS-${year}-${String(next).padStart(5, '0')}`;
  }

  async create(dto: CreatePurchaseDto, warehouseId: string): Promise<Purchase> {
    if (!dto.items?.length) {
      throw new BadRequestException('En az bir alış kalemi gerekli');
    }
    if (!warehouseId?.trim()) {
      throw new BadRequestException('Depo seçimi zorunludur');
    }
    for (const row of dto.items) {
      if (!row.productId?.trim()) throw new BadRequestException('Ürün seçimi zorunludur');
      if (!row.quantity || Number(row.quantity) < 1) throw new BadRequestException('Miktar 1 veya üzeri olmalıdır');
      if (Number(row.unitPrice) < 0) throw new BadRequestException('Birim fiyat negatif olamaz');
      const product = await this.productService.findOne(row.productId);
      if (!product) throw new BadRequestException(`Ürün bulunamadı: ${row.productId}`);
    }
    if (dto.isReturn) {
      for (const row of dto.items) {
        const stock = await this.stockService.getStock(row.productId, warehouseId);
        const available = Number(stock.quantity) - Number(stock.reservedQuantity ?? 0);
        if (available < row.quantity) {
          const product = await this.productService.findOne(row.productId);
          throw new BadRequestException(
            `Yetersiz stok (iade): ${product?.name ?? row.productId} - Depoda ${available} adet, ${row.quantity} adet iade girilemez`,
          );
        }
      }
    }

    const purchaseNumber = await this.nextPurchaseNumber();
    const purchase = this.purchaseRepo.create({
      purchaseNumber,
      supplierId: dto.supplierId,
      purchaseDate: new Date(dto.purchaseDate),
      dueDate: dto.dueDate ? new Date(dto.dueDate) : undefined,
      isReturn: dto.isReturn ?? false,
      notes: dto.notes,
    });
    await this.purchaseRepo.save(purchase);
    let subtotal = 0;
    for (const row of dto.items) {
      const product = await this.productService.findOne(row.productId);
      const kdvRate = row.kdvRate ?? Number(product.kdvRate) ?? 18;
      const lineNet = row.unitPrice * row.quantity;
      const lineKdv = lineNet * (kdvRate / 100);
      const lineTotal = lineNet + lineKdv;
      const item = this.itemRepo.create({
        purchaseId: purchase.id,
        productId: row.productId,
        unitPrice: row.unitPrice,
        quantity: row.quantity,
        kdvRate,
        lineTotal,
      });
      await this.itemRepo.save(item);
      subtotal += lineNet;
      if (!dto.isReturn) {
        await this.stockService.movement(
          row.productId,
          warehouseId,
          'giris',
          row.quantity,
          { refType: 'alis', refId: purchase.id, description: `Alış ${purchaseNumber}` },
        );
      } else {
        await this.stockService.movement(
          row.productId,
          warehouseId,
          'cikis',
          row.quantity,
          { refType: 'iade', refId: purchase.id, description: `İade ${purchaseNumber}` },
        );
      }
    }
    const kdvTotal = (await this.itemRepo.find({ where: { purchaseId: purchase.id } })).reduce(
      (sum, i) => sum + Number(i.lineTotal) - Number(i.unitPrice) * i.quantity,
      0,
    );
    purchase.subtotal = subtotal;
    purchase.kdvTotal = kdvTotal;
    purchase.grandTotal = subtotal + kdvTotal;
    await this.purchaseRepo.save(purchase);

    const grandTotalNum = Number(purchase.grandTotal);
    if (
      !dto.isReturn &&
      (dto.paymentType === PaymentType.NAKIT || (dto.paymentType as string) === 'nakit') &&
      dto.kasaId &&
      grandTotalNum > 0
    ) {
      await this.kasaHareketService.cikis({
        kasaId: dto.kasaId,
        amount: grandTotalNum,
        movementDate: dto.purchaseDate,
        description: `Alış ${purchaseNumber}`,
      });
    }

    if (grandTotalNum >= LARGE_PURCHASE_THRESHOLD) {
      this.logger.log(
        `Büyük tutarlı alış: ${purchaseNumber}, tutar=${grandTotalNum.toFixed(2)} ₺, tedarikçi=${dto.supplierId}`,
      );
    }

    return this.findOne(purchase.id);
  }

  async findAll(params?: {
    supplierId?: string;
    dateFrom?: string;
    dateTo?: string;
    purchaseNumber?: string;
    isReturn?: boolean;
    page?: number;
    limit?: number;
  }): Promise<PaginatedResult<Purchase>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const qb = this.purchaseRepo
      .createQueryBuilder('p')
      .leftJoinAndSelect('p.supplier', 's')
      .leftJoinAndSelect('p.items', 'i')
      .leftJoinAndSelect('i.product', 'prod')
      .orderBy('p.purchaseDate', 'DESC');
    if (params?.supplierId) qb.andWhere('p.supplierId = :supplierId', { supplierId: params.supplierId });
    if (params?.dateFrom) qb.andWhere('p.purchaseDate >= :dateFrom', { dateFrom: params.dateFrom });
    if (params?.dateTo) qb.andWhere('p.purchaseDate <= :dateTo', { dateTo: params.dateTo });
    if (params?.purchaseNumber?.trim()) qb.andWhere('p.purchaseNumber LIKE :pn', { pn: `%${params.purchaseNumber.trim()}%` });
    if (params?.isReturn !== undefined) qb.andWhere('p.isReturn = :isReturn', { isReturn: params.isReturn });
    const total = await qb.getCount();
    const data = await qb.skip(skip).take(limit).getMany();
    return {
      data,
      total,
      page,
      limit,
      totalPages: Math.ceil(total / limit) || 1,
    };
  }

  async findOne(id: string): Promise<Purchase> {
    const p = await this.purchaseRepo.findOne({
      where: { id },
      relations: ['supplier', 'items', 'items.product'],
    });
    if (!p) throw new Error('Alış fişi bulunamadı');
    return p;
  }

  async update(id: string, data: { purchaseDate?: string; dueDate?: string; notes?: string; paidAmount?: number }): Promise<Purchase> {
    const purchase = await this.findOne(id);
    if (data.purchaseDate !== undefined) purchase.purchaseDate = data.purchaseDate ? new Date(data.purchaseDate) : purchase.purchaseDate;
    if (data.dueDate !== undefined) (purchase as { dueDate: Date | null }).dueDate = data.dueDate ? new Date(data.dueDate) : null;
    if (data.notes !== undefined) purchase.notes = data.notes;
    if (data.paidAmount !== undefined) purchase.paidAmount = data.paidAmount;
    await this.purchaseRepo.save(purchase);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.findOne(id);
    const movements = await this.movementRepo.find({
      where: [{ refType: 'alis', refId: id }, { refType: 'iade', refId: id }],
    });
    for (const m of movements) {
      const qty = Math.abs(Number(m.quantity));
      const reverseType = m.quantity > 0 ? 'cikis' : 'giris';
      await this.stockService.movement(
        m.productId,
        m.warehouseId,
        reverseType as 'giris' | 'cikis',
        qty,
        { refType: 'manuel', description: 'Alış iptal' },
      );
    }
    await this.itemRepo.delete({ purchaseId: id });
    await this.purchaseRepo.delete(id);
  }
}
