import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ExpenseCategory } from '../../entities/expense-category.entity';

@Injectable()
export class ExpenseCategoryService {
  constructor(
    @InjectRepository(ExpenseCategory)
    private repo: Repository<ExpenseCategory>,
  ) {}

  async findAll(): Promise<ExpenseCategory[]> {
    return this.repo.find({ order: { sortOrder: 'ASC', name: 'ASC' } });
  }

  async create(data: { name: string; sortOrder?: number }): Promise<ExpenseCategory> {
    const c = this.repo.create(data);
    return this.repo.save(c);
  }

  async update(id: string, data: Partial<ExpenseCategory>): Promise<ExpenseCategory> {
    await this.repo.update(id, data as Record<string, unknown>);
    return this.repo.findOne({ where: { id } }) as Promise<ExpenseCategory>;
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }
}
