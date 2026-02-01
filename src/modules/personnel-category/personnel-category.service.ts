import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { PersonnelCategory } from '../../entities/personnel-category.entity';

@Injectable()
export class PersonnelCategoryService {
  constructor(
    @InjectRepository(PersonnelCategory)
    private repo: Repository<PersonnelCategory>,
  ) {}

  async findAll(): Promise<PersonnelCategory[]> {
    return this.repo.find({ order: { name: 'ASC' } });
  }

  async create(data: { name: string }): Promise<PersonnelCategory> {
    const c = this.repo.create(data);
    return this.repo.save(c);
  }
}
