import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Customer } from '../../entities/customer.entity';
import { CustomerPaymentService } from '../customer-payment/customer-payment.service';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';

export type CustomerWithBalance = Customer & { balance?: number; salesCount?: number };

@Injectable()
export class CustomerService {
  constructor(
    @InjectRepository(Customer)
    private repo: Repository<Customer>,
    private customerPaymentService: CustomerPaymentService,
  ) {}

  async create(data: Partial<Customer>): Promise<Customer> {
    const c = this.repo.create(data);
    return this.repo.save(c);
  }

  async findAll(params?: { active?: boolean; search?: string; page?: number; limit?: number; withBalance?: boolean }): Promise<PaginatedResult<CustomerWithBalance>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const qb = this.repo.createQueryBuilder('c').orderBy('c.name', 'ASC');
    if (params?.active !== undefined) qb.andWhere('c.isActive = :active', { active: params.active });
    if (params?.search?.trim()) {
      qb.andWhere('(c.name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)', {
        search: `%${params.search.trim()}%`,
      });
    }
    const total = await qb.getCount();
    const data = await qb.skip(skip).take(limit).getMany() as CustomerWithBalance[];
    if (params?.withBalance && data.length > 0) {
      const ids = data.map((c) => c.id);
      const map = await this.customerPaymentService.getBalancesAndSalesCountMap(ids);
      for (const c of data) {
        const info = map.get(c.id);
        if (info) {
          c.balance = info.balance;
          c.salesCount = info.salesCount;
        } else {
          c.balance = 0;
          c.salesCount = 0;
        }
      }
    }
    return {
      data,
      total,
      page,
      limit,
      totalPages: Math.ceil(total / limit) || 1,
    };
  }

  async findLatest(limit: number = 5): Promise<Customer[]> {
    return this.repo.find({
      order: { createdAt: 'DESC' },
      take: Math.min(limit, 50),
    });
  }

  async findOne(id: string): Promise<Customer> {
    const c = await this.repo.findOne({ where: { id } });
    if (!c) throw new Error('Müşteri bulunamadı');
    return c;
  }

  async update(id: string, data: Partial<Customer>): Promise<Customer> {
    await this.repo.update(id, data as any);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }
}
