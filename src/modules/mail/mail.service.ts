import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as nodemailer from 'nodemailer';
import { MailLog } from '../../entities/mail-log.entity';
import { MailStatus } from '../../common/enums/mail-status.enum';

@Injectable()
export class MailService {
  private transporter: nodemailer.Transporter | null = null;

  constructor(
    private config: ConfigService,
    @InjectRepository(MailLog)
    private logRepo: Repository<MailLog>,
  ) {
    const host = this.config.get('MAIL_HOST');
    if (host) {
      this.transporter = nodemailer.createTransport({
        host,
        port: this.config.get('MAIL_PORT') || 587,
        secure: false,
        auth: {
          user: this.config.get('MAIL_USER'),
          pass: this.config.get('MAIL_PASSWORD'),
        },
      });
    }
  }

  async send(options: {
    to: string;
    cc?: string;
    subject: string;
    text?: string;
    html?: string;
    entityType?: string;
    entityId?: string;
    userId?: string;
  }): Promise<MailLog> {
    const log = this.logRepo.create({
      to: options.to,
      cc: options.cc,
      subject: options.subject,
      body: options.html || options.text,
      status: MailStatus.GONDERILDI,
      entityType: options.entityType,
      entityId: options.entityId,
      userId: options.userId,
      sentAt: new Date(),
    });
    await this.logRepo.save(log);
    if (this.transporter) {
      try {
        await this.transporter.sendMail({
          from: this.config.get('MAIL_FROM') || 'noreply@mobilyatakip.local',
          to: options.to,
          cc: options.cc,
          subject: options.subject,
          text: options.text,
          html: options.html,
        });
      } catch (err: any) {
        log.errorMessage = err?.message;
        await this.logRepo.save(log);
        throw err;
      }
    }
    return log;
  }

  async markRead(logId: string): Promise<MailLog> {
    const log = await this.logRepo.findOne({ where: { id: logId } });
    if (!log) throw new Error('Mail log bulunamadı');
    log.status = MailStatus.OKUNDU;
    log.readAt = new Date();
    return this.logRepo.save(log);
  }

  async getLogs(params?: { entityType?: string; entityId?: string }): Promise<MailLog[]> {
    const where: any = {};
    if (params?.entityType) where.entityType = params.entityType;
    if (params?.entityId) where.entityId = params.entityId;
    return this.logRepo.find({ where, order: { sentAt: 'DESC' }, take: 100 });
  }
}
