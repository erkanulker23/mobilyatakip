import { Injectable, OnModuleInit } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ExpenseCategory } from '../entities/expense-category.entity';

const DEFAULT_CATEGORIES = [
  'Kira',
  'Elektrik',
  'Su',
  'Doğalgaz',
  'İnternet / Telefon',
  'Kırtasiye',
  'Temizlik',
  'Yakıt',
  'Bakım / Onarım',
  'Sigorta',
  'Vergi',
  'Personel',
  'Reklam',
  'Diğer',
];

@Injectable()
export class ExpenseCategorySeedService implements OnModuleInit {
  constructor(
    @InjectRepository(ExpenseCategory)
    private repo: Repository<ExpenseCategory>,
  ) {}

  async onModuleInit() {
    const count = await this.repo.count();
    if (count > 0) return;
    for (let i = 0; i < DEFAULT_CATEGORIES.length; i++) {
      await this.repo.save(
        this.repo.create({ name: DEFAULT_CATEGORIES[i], sortOrder: i }),
      );
    }
    console.log('[Seed] Masraf kategorileri oluşturuldu.');
  }
}
