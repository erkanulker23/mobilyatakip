import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Kasa } from '../../entities/kasa.entity';

@Injectable()
export class KasaService {
  constructor(
    @InjectRepository(Kasa)
    private repo: Repository<Kasa>,
  ) {}

  async create(data: Partial<Kasa>): Promise<Kasa> {
    const k = this.repo.create(data);
    return this.repo.save(k);
  }

  async findAll(active?: boolean): Promise<Kasa[]> {
    const where: Record<string, unknown> = {};
    if (active !== undefined) where.isActive = active;
    return this.repo.find({ where, order: { name: 'ASC' } });
  }

  async findOne(id: string): Promise<Kasa> {
    const k = await this.repo.findOne({ where: { id } });
    if (!k) throw new Error('Kasa bulunamadı');
    return k;
  }

  async update(id: string, data: Partial<Kasa>): Promise<Kasa> {
    await this.repo.update(id, data as Record<string, unknown>);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }
}
