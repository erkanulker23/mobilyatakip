import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { Notification } from '../../entities/notification.entity';

@Injectable()
export class NotificationService {
  constructor(
    @InjectRepository(Notification)
    private repo: Repository<Notification>,
  ) {}

  async create(data: {
    userId: string;
    type: string;
    title: string;
    body?: string;
    entityType?: string;
    entityId?: string;
  }): Promise<Notification> {
    const n = this.repo.create(data);
    return this.repo.save(n);
  }

  async findByUser(userId: string, unreadOnly?: boolean): Promise<Notification[]> {
    const where: any = { userId };
    if (unreadOnly) where.isRead = false;
    return this.repo.find({ where, order: { createdAt: 'DESC' }, take: 50 });
  }

  async markRead(id: string): Promise<Notification> {
    const n = await this.repo.findOne({ where: { id } });
    if (!n) throw new Error('Bildirim bulunamadı');
    n.isRead = true;
    n.readAt = new Date();
    return this.repo.save(n);
  }

  async markAllRead(userId: string): Promise<void> {
    await this.repo.update({ userId, isRead: false }, { isRead: true, readAt: new Date() });
  }
}
