import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ServiceTicket } from '../../entities/service-ticket.entity';
import { ServiceTicketDetail } from '../../entities/service-ticket-detail.entity';
import { ServicePart } from '../../entities/service-part.entity';
import { ServiceTicketStatus } from '../../common/enums/service-ticket-status.enum';
import { PaginatedResult, normalizePagination } from '../../common/interfaces/pagination.interface';
import { StockService } from '../stock/stock.service';

@Injectable()
export class ServiceTicketService {
  constructor(
    @InjectRepository(ServiceTicket)
    private ticketRepo: Repository<ServiceTicket>,
    @InjectRepository(ServiceTicketDetail)
    private detailRepo: Repository<ServiceTicketDetail>,
    @InjectRepository(ServicePart)
    private partRepo: Repository<ServicePart>,
    private stockService: StockService,
  ) {}

  private async nextTicketNumber(): Promise<string> {
    const year = new Date().getFullYear();
    const last = await this.ticketRepo
      .createQueryBuilder('t')
      .where('t.ticketNumber LIKE :prefix', { prefix: `SSH-${year}-%` })
      .orderBy('t.ticketNumber', 'DESC')
      .getOne();
    const next = last ? parseInt(last.ticketNumber.split('-').pop() || '0', 10) + 1 : 1;
    return `SSH-${year}-${String(next).padStart(5, '0')}`;
  }

  async create(data: {
    saleId: string;
    customerId: string;
    underWarranty: boolean;
    issueType: string;
    description?: string;
    assignedUserId?: string;
    assignedVehiclePlate?: string;
    assignedDriverName?: string;
    assignedDriverPhone?: string;
    notes?: string;
    images?: string[];
    serviceChargeAmount?: number | null;
  }): Promise<ServiceTicket> {
    const ticketNumber = await this.nextTicketNumber();
    const t = this.ticketRepo.create({
      ticketNumber,
      ...data,
      images: data.images ?? undefined,
      serviceChargeAmount: data.serviceChargeAmount ?? null,
      status: ServiceTicketStatus.ACILDI,
    });
    return this.ticketRepo.save(t);
  }

  async findAll(params?: {
    customerId?: string;
    saleId?: string;
    status?: ServiceTicketStatus;
    openedAtFrom?: string;
    openedAtTo?: string;
    search?: string;
    page?: number;
    limit?: number;
  }): Promise<PaginatedResult<ServiceTicket>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const qb = this.ticketRepo
      .createQueryBuilder('t')
      .leftJoinAndSelect('t.sale', 's')
      .leftJoinAndSelect('t.customer', 'c')
      .leftJoinAndSelect('t.assignedUser', 'u')
      .leftJoinAndSelect('t.details', 'd')
      .orderBy('t.openedAt', 'DESC');
    if (params?.customerId) qb.andWhere('t.customerId = :customerId', { customerId: params.customerId });
    if (params?.saleId) qb.andWhere('t.saleId = :saleId', { saleId: params.saleId });
    if (params?.status) qb.andWhere('t.status = :status', { status: params.status });
    if (params?.openedAtFrom) qb.andWhere('t.openedAt >= :openedAtFrom', { openedAtFrom: params.openedAtFrom });
    if (params?.openedAtTo) qb.andWhere('t.openedAt <= :openedAtTo', { openedAtTo: params.openedAtTo });
    if (params?.search?.trim()) {
      qb.andWhere('(t.ticketNumber LIKE :search OR t.issueType LIKE :search)', { search: `%${params.search.trim()}%` });
    }
    const total = await qb.getCount();
    const data = await qb.skip(skip).take(limit).getMany();
    return {
      data,
      total,
      page,
      limit,
      totalPages: Math.ceil(total / limit) || 1,
    };
  }

  async findOne(id: string): Promise<ServiceTicket> {
    const t = await this.ticketRepo.findOne({
      where: { id },
      relations: ['sale', 'customer', 'assignedUser', 'details', 'details.parts', 'details.parts.product', 'details.user'],
    });
    if (!t) throw new Error('Servis kaydı bulunamadı');
    return t;
  }

  async updateStatus(id: string, status: ServiceTicketStatus): Promise<ServiceTicket> {
    const t = await this.findOne(id);
    t.status = status;
    if (status === ServiceTicketStatus.KAPANDI) t.closedAt = new Date();
    await this.ticketRepo.save(t);
    return this.findOne(id);
  }

  async addDetail(ticketId: string, userId: string, action: string, notes?: string, parts?: { productId: string; quantity: number }[], warehouseId?: string, images?: string[]): Promise<ServiceTicketDetail> {
    const d = this.detailRepo.create({ ticketId, userId, action, notes, images });
    await this.detailRepo.save(d);
    if (parts?.length && warehouseId) {
      for (const p of parts) {
        await this.partRepo.save(this.partRepo.create({ detailId: d.id, productId: p.productId, quantity: p.quantity }));
        await this.stockService.movement(p.productId, warehouseId, 'cikis', p.quantity, { refType: 'ssh', refId: ticketId, userId, description: 'SSH parça kullanımı' });
      }
    }
    return this.detailRepo.findOne({ where: { id: d.id }, relations: ['parts', 'parts.product'] }) as Promise<ServiceTicketDetail>;
  }

  async getOpenCount(): Promise<number> {
    return this.ticketRepo
      .createQueryBuilder('t')
      .where('t.status != :status', { status: ServiceTicketStatus.KAPANDI })
      .getCount();
  }

  async getAverageResolutionTime(): Promise<number | null> {
    const closed = await this.ticketRepo.find({ where: { status: ServiceTicketStatus.KAPANDI }, select: ['openedAt', 'closedAt'] });
    if (!closed.length) return null;
    const totalMs = closed.reduce((s, t) => s + (t.closedAt ? new Date(t.closedAt).getTime() - new Date(t.openedAt).getTime() : 0), 0);
    return Math.round(totalMs / closed.length / (24 * 60 * 60 * 1000));
  }
}
