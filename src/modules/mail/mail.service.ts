import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as nodemailer from 'nodemailer';
import { MailLog } from '../../entities/mail-log.entity';
import { MailStatus } from '../../common/enums/mail-status.enum';
import { CompanyService } from '../company/company.service';

@Injectable()
export class MailService {
  constructor(
    private config: ConfigService,
    private companyService: CompanyService,
    @InjectRepository(MailLog)
    private logRepo: Repository<MailLog>,
  ) {}

  /** Şirket ayarlarından veya env'den SMTP transporter oluşturur. */
  private async getTransporter(): Promise<nodemailer.Transporter | null> {
    const company = await this.companyService.findOne();
    const host = company?.mailHost || this.config.get('MAIL_HOST');
    if (!host) return null;
    const port = company?.mailPort ?? this.config.get('MAIL_PORT') ?? 587;
    const secure = company?.mailSecure ?? false;
    const user = company?.mailUser || this.config.get('MAIL_USER');
    const pass = company?.mailPassword || this.config.get('MAIL_PASSWORD');
    return nodemailer.createTransport({
      host,
      port: Number(port),
      secure,
      auth: user && pass ? { user, pass } : undefined,
    });
  }

  private async getFromAddress(): Promise<string> {
    const company = await this.companyService.findOne();
    return company?.mailFrom || this.config.get('MAIL_FROM') || 'noreply@mobilyatakip.local';
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
    const transporter = await this.getTransporter();
    if (transporter) {
      try {
        await transporter.sendMail({
          from: await this.getFromAddress(),
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

  /** Test e-postası gönderir (Ayarlar sayfasından bağlantı testi). */
  async sendTest(to: string): Promise<{ ok: boolean; message?: string }> {
    const transporter = await this.getTransporter();
    if (!transporter) {
      return { ok: false, message: 'E-posta ayarları eksik. Ayarlar > E-posta (SMTP) bölümünden SMTP bilgilerini girin.' };
    }
    try {
      const from = await this.getFromAddress();
      await transporter.sendMail({
        from,
        to,
        subject: 'Mobilya Takip – Test E-postası',
        text: 'Bu mesaj Mobilya Takip uygulamasından gönderilen bir test e-postasıdır. SMTP ayarlarınız çalışıyor.',
        html: '<p>Bu mesaj <strong>Mobilya Takip</strong> uygulamasından gönderilen bir test e-postasıdır.</p><p>SMTP ayarlarınız çalışıyor.</p>',
      });
      return { ok: true };
    } catch (err: any) {
      return { ok: false, message: err?.message || 'E-posta gönderilemedi.' };
    }
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
