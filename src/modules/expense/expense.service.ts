import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Expense } from '../../entities/expense.entity';

@Injectable()
export class ExpenseService {
  constructor(
    @InjectRepository(Expense)
    private repo: Repository<Expense>,
  ) {}

  async create(data: Partial<Expense>): Promise<Expense> {
    if (!data.kasaId) {
      throw new Error('Masraf için kasa (kasaId) zorunludur.');
    }
    const e = this.repo.create(data);
    return this.repo.save(e);
  }

  async findAll(params?: { kasaId?: string; from?: string; to?: string }): Promise<Expense[]> {
    const qb = this.repo
      .createQueryBuilder('e')
      .leftJoinAndSelect('e.kasa', 'k')
      .leftJoinAndSelect('e.createdByUser', 'createdByUser')
      .orderBy('e.expenseDate', 'DESC')
      .addOrderBy('e.createdAt', 'DESC');
    if (params?.kasaId) qb.andWhere('e.kasaId = :kasaId', { kasaId: params.kasaId });
    if (params?.from) qb.andWhere('e.expenseDate >= :from', { from: params.from });
    if (params?.to) qb.andWhere('e.expenseDate <= :to', { to: params.to });
    return qb.getMany();
  }

  async findOne(id: string): Promise<Expense> {
    const e = await this.repo.findOne({ where: { id }, relations: ['kasa', 'createdByUser'] });
    if (!e) throw new Error('Masraf bulunamadı');
    return e;
  }

  async update(id: string, data: Partial<Expense>): Promise<Expense> {
    await this.repo.update(id, data as Record<string, unknown>);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }
}
