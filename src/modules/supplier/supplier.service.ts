import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Supplier } from '../../entities/supplier.entity';
import { Product } from '../../entities/product.entity';
import { SaleItem } from '../../entities/sale-item.entity';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';

@Injectable()
export class SupplierService {
  constructor(
    @InjectRepository(Supplier)
    private repo: Repository<Supplier>,
    @InjectRepository(Product)
    private productRepo: Repository<Product>,
    @InjectRepository(SaleItem)
    private saleItemRepo: Repository<SaleItem>,
  ) {}

  async create(data: Partial<Supplier>): Promise<Supplier> {
    const s = this.repo.create(data);
    return this.repo.save(s);
  }

  async findAll(active?: boolean): Promise<Supplier[]> {
    const where: any = {};
    if (active !== undefined) where.isActive = active;
    return this.repo.find({ where, order: { name: 'ASC' } });
  }

  async findAllWithProducts(params?: {
    active?: boolean;
    page?: number;
    limit?: number;
  }): Promise<PaginatedResult<Supplier>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const qb = this.repo
      .createQueryBuilder('s')
      .leftJoinAndSelect('s.products', 'p')
      .orderBy('s.name', 'ASC')
      .addOrderBy('p.name', 'ASC');
    if (params?.active !== undefined) qb.andWhere('s.isActive = :active', { active: params.active });
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

  async findOne(id: string): Promise<Supplier> {
    const s = await this.repo.findOne({ where: { id }, relations: ['products'] });
    if (!s) throw new Error('Tedarikçi bulunamadı');
    return s;
  }

  /** Bu tedarikçinin ürünlerinden yapılan satış sayısı ve kaç farklı müşteriye satış yapıldığı. */
  async getSalesStats(supplierId: string): Promise<{ salesCount: number; customerCount: number }> {
    const raw = await this.saleItemRepo
      .createQueryBuilder('si')
      .innerJoin('si.sale', 's')
      .innerJoin('si.product', 'p')
      .select('COUNT(DISTINCT s.id)', 'salesCount')
      .addSelect('COUNT(DISTINCT s.customerId)', 'customerCount')
      .where('p.supplierId = :supplierId', { supplierId })
      .getRawOne<{ salesCount: string; customerCount: string }>();
    return {
      salesCount: Number(raw?.salesCount ?? 0),
      customerCount: Number(raw?.customerCount ?? 0),
    };
  }

  async update(id: string, data: Partial<Supplier>): Promise<Supplier> {
    await this.repo.update(id, data as any);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.productRepo.delete({ supplierId: id });
    await this.repo.delete(id);
  }
}
