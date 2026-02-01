import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { AuditLog } from '../../entities/audit-log.entity';

@Injectable()
export class AuditLogService {
  constructor(
    @InjectRepository(AuditLog)
    private repo: Repository<AuditLog>,
  ) {}

  async log(data: {
    userId?: string;
    entity: string;
    entityId?: string;
    action: string;
    oldValue?: Record<string, unknown>;
    newValue?: Record<string, unknown>;
    ipAddress?: string;
    userAgent?: string;
  }): Promise<AuditLog> {
    const log = this.repo.create(data);
    return this.repo.save(log);
  }

  async findByEntity(entity: string, entityId?: string, limit = 100): Promise<AuditLog[]> {
    const where: any = { entity };
    if (entityId) where.entityId = entityId;
    return this.repo.find({ where, order: { createdAt: 'DESC' }, take: limit });
  }

  async findByUser(userId: string, limit = 100): Promise<AuditLog[]> {
    return this.repo.find({ where: { userId }, order: { createdAt: 'DESC' }, take: limit });
  }
}
