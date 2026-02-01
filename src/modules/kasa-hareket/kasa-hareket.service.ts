import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { KasaHareket, KasaHareketType } from '../../entities/kasa-hareket.entity';

@Injectable()
export class KasaHareketService {
  constructor(
    @InjectRepository(KasaHareket)
    private readonly repo: Repository<KasaHareket>,
  ) {}

  async create(data: Partial<KasaHareket>): Promise<KasaHareket> {
    const h = this.repo.create(data);
    return this.repo.save(h);
  }

  async findAllByKasa(kasaId: string, from?: string, to?: string): Promise<KasaHareket[]> {
    const qb = this.repo
      .createQueryBuilder('h')
      .leftJoinAndSelect('h.kasa', 'k')
      .leftJoinAndSelect('h.fromKasa', 'fromKasa')
      .leftJoinAndSelect('h.toKasa', 'toKasa')
      .leftJoinAndSelect('h.createdByUser', 'createdByUser')
      .where(
        '(h.kasaId = :kasaId OR h.fromKasaId = :kasaId OR h.toKasaId = :kasaId)',
        { kasaId },
      )
      .orderBy('h.movementDate', 'DESC')
      .addOrderBy('h.createdAt', 'DESC');
    if (from) qb.andWhere('h.movementDate >= :from', { from });
    if (to) qb.andWhere('h.movementDate <= :to', { to });
    return qb.getMany();
  }

  async findOne(id: string): Promise<KasaHareket> {
    const h = await this.repo.findOne({
      where: { id },
      relations: ['kasa', 'fromKasa', 'toKasa', 'createdByUser'],
    });
    if (!h) throw new Error('Kasa hareketi bulunamadı');
    return h;
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }

  /** Kasaya para ekleme (giriş) */
  async giris(data: { kasaId: string; amount: number; movementDate: string; description?: string; userId?: string }): Promise<KasaHareket> {
    return this.create({
      type: 'giris' as KasaHareketType,
      kasaId: data.kasaId,
      amount: data.amount,
      movementDate: new Date(data.movementDate),
      description: data.description ?? undefined,
      createdBy: data.userId ?? undefined,
    });
  }

  /** Kasadan çıkış (ödeme / masraf) */
  async cikis(data: { kasaId: string; amount: number; movementDate: string; description?: string; userId?: string }): Promise<KasaHareket> {
    return this.create({
      type: 'cikis' as KasaHareketType,
      kasaId: data.kasaId,
      amount: data.amount,
      movementDate: new Date(data.movementDate),
      description: data.description ?? undefined,
      createdBy: data.userId ?? undefined,
    });
  }

  /** Hesaplar arası virman */
  async virman(data: {
    fromKasaId: string;
    toKasaId: string;
    amount: number;
    movementDate: string;
    description?: string;
    userId?: string;
  }): Promise<KasaHareket> {
    if (data.fromKasaId === data.toKasaId) throw new Error('Kaynak ve hedef kasa aynı olamaz');
    return this.create({
      type: 'virman' as KasaHareketType,
      fromKasaId: data.fromKasaId,
      toKasaId: data.toKasaId,
      amount: data.amount,
      movementDate: new Date(data.movementDate),
      description: data.description ?? undefined,
      createdBy: data.userId ?? undefined,
    });
  }
}
