import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Warehouse } from '../../entities/warehouse.entity';

@Injectable()
export class WarehouseService {
  constructor(
    @InjectRepository(Warehouse)
    private repo: Repository<Warehouse>,
  ) {}

  async create(data: Partial<Warehouse>): Promise<Warehouse> {
    const w = this.repo.create(data);
    return this.repo.save(w);
  }

  async findAll(active?: boolean): Promise<Warehouse[]> {
    const where: any = {};
    if (active !== undefined) where.isActive = active;
    return this.repo.find({ where, order: { name: 'ASC' } });
  }

  async findOne(id: string): Promise<Warehouse> {
    const w = await this.repo.findOne({ where: { id } });
    if (!w) throw new Error('Depo bulunamadı');
    return w;
  }

  async update(id: string, data: Partial<Warehouse>): Promise<Warehouse> {
    await this.repo.update(id, data as any);
    return this.findOne(id);
  }
}
