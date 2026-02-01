import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Personnel } from '../../entities/personnel.entity';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';

@Injectable()
export class PersonnelService {
  constructor(
    @InjectRepository(Personnel)
    private repo: Repository<Personnel>,
  ) {}

  async create(data: Partial<Personnel>): Promise<Personnel> {
    const p = this.repo.create(data);
    return this.repo.save(p);
  }

  async findAll(params?: { active?: boolean; page?: number; limit?: number }): Promise<PaginatedResult<Personnel>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const where: Record<string, unknown> = {};
    if (params?.active !== undefined) where.isActive = params.active;
    const [data, total] = await this.repo.findAndCount({
      where,
      order: { name: 'ASC' },
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

  async findOne(id: string): Promise<Personnel> {
    const p = await this.repo.findOne({ where: { id } });
    if (!p) throw new Error('Personel bulunamadı');
    return p;
  }

  async update(id: string, data: Partial<Personnel>): Promise<Personnel> {
    await this.repo.update(id, data as Record<string, unknown>);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }
}
