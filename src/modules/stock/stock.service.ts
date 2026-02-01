import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Stock } from '../../entities/stock.entity';
import { StockMovement, StockMovementType, StockRefType } from '../../entities/stock-movement.entity';

@Injectable()
export class StockService {
  constructor(
    @InjectRepository(Stock)
    private stockRepo: Repository<Stock>,
    @InjectRepository(StockMovement)
    private movementRepo: Repository<StockMovement>,
  ) {}

  async getStock(productId: string, warehouseId: string): Promise<Stock> {
    let s = await this.stockRepo.findOne({ where: { productId, warehouseId }, relations: ['product', 'warehouse'] });
    if (!s) {
      s = this.stockRepo.create({ productId, warehouseId, quantity: 0, reservedQuantity: 0 });
      await this.stockRepo.save(s);
    }
    return s;
  }

  async getByWarehouse(warehouseId: string): Promise<Stock[]> {
    return this.stockRepo.find({
      where: { warehouseId },
      relations: ['product'],
      order: { product: { name: 'ASC' } },
    });
  }

  async getByProduct(productId: string): Promise<Stock[]> {
    return this.stockRepo.find({
      where: { productId },
      relations: ['warehouse'],
    });
  }

  async getLowStock(warehouseId?: string): Promise<Stock[]> {
    const qb = this.stockRepo
      .createQueryBuilder('s')
      .innerJoin('s.product', 'p')
      .addSelect(['p.id', 'p.name', 'p.sku', 'p.minStockLevel'])
      .where('(s.quantity - s.reservedQuantity) <= p.minStockLevel')
      .andWhere('p.minStockLevel > 0');
    if (warehouseId) qb.andWhere('s.warehouseId = :warehouseId', { warehouseId });
    return qb.getMany();
  }

  async movement(
    productId: string,
    warehouseId: string,
    type: StockMovementType,
    quantity: number,
    opts?: { refType?: StockRefType; refId?: string; userId?: string; description?: string },
  ): Promise<Stock> {
    const stock = await this.getStock(productId, warehouseId);
    const q = Number(stock.quantity);
    const r = Number(stock.reservedQuantity);
    const available = q - r;
    if (type === 'cikis' || type === 'transfer') {
      if (quantity > available) throw new Error('Yetersiz stok');
    }
    const delta = type === 'giris' ? quantity : type === 'düzeltme' ? 0 : -quantity;
    if (type === 'düzeltme') {
      stock.quantity = quantity;
      await this.stockRepo.save(stock);
    } else {
      stock.quantity = Number(stock.quantity) + delta;
      await this.stockRepo.save(stock);
    }
    await this.movementRepo.save(
      this.movementRepo.create({
        productId,
        warehouseId,
        type,
        quantity: type === 'cikis' || type === 'transfer' ? -quantity : quantity,
        refType: opts?.refType,
        refId: opts?.refId,
        userId: opts?.userId,
        description: opts?.description,
      }),
    );
    return this.getStock(productId, warehouseId);
  }

  async reserve(productId: string, warehouseId: string, quantity: number): Promise<Stock> {
    const stock = await this.getStock(productId, warehouseId);
    const available = Number(stock.quantity) - Number(stock.reservedQuantity);
    if (quantity > available) throw new Error('Yetersiz stok');
    await this.stockRepo.update(
      { productId, warehouseId },
      { reservedQuantity: () => `reserved_quantity + ${quantity}` } as any,
    );
    return this.getStock(productId, warehouseId);
  }

  async getMovements(productId: string, warehouseId?: string, limit = 50): Promise<StockMovement[]> {
    const where: any = { productId };
    if (warehouseId) where.warehouseId = warehouseId;
    return this.movementRepo.find({
      where,
      relations: ['warehouse'],
      order: { createdAt: 'DESC' },
      take: limit,
    });
  }
}
