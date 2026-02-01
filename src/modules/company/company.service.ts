import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Company } from '../../entities/company.entity';

@Injectable()
export class CompanyService {
  constructor(
    @InjectRepository(Company)
    private repo: Repository<Company>,
  ) {}

  async findOne(): Promise<Company | null> {
    return this.repo.findOne({ where: {} });
  }

  async upsert(data: Partial<Company>): Promise<Company> {
    let company = await this.repo.findOne({ where: {} });
    if (!company) {
      company = this.repo.create(data);
    } else {
      Object.assign(company, data);
    }
    return this.repo.save(company);
  }
}
