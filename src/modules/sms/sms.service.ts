import { Injectable } from '@nestjs/common';
import { CompanyService } from '../company/company.service';

@Injectable()
export class SmsService {
  constructor(private company: CompanyService) {}

  async isConfigured(): Promise<boolean> {
    const company = await this.company.findOne();
    return !!(company?.ntgsmUsername && company?.ntgsmPassword && (company?.ntgsmApiUrl || company?.ntgsmOriginator));
  }

  async send(phone: string, message: string): Promise<{ ok: boolean; message?: string }> {
    const company = await this.company.findOne();
    if (!company?.ntgsmUsername || !company?.ntgsmPassword) {
      return { ok: false, message: 'SMS ayarları eksik. Ayarlar > NTGSM SMS API bölümünü doldurun.' };
    }
    const gsm = phone.replace(/\D/g, '').replace(/^0/, '90');
    const msgheader = company.ntgsmOriginator || 'MOBILYA';
    const url = company.ntgsmApiUrl?.trim() || 'https://api.netgsm.com.tr/sms/send/get';
    const params = new URLSearchParams({
      usercode: company.ntgsmUsername,
      password: company.ntgsmPassword,
      gsm,
      message,
      msgheader,
    });
    try {
      const res = await fetch(`${url}?${params.toString()}`, { method: 'GET' });
      const text = await res.text();
      if (res.ok && (text === '00' || text.startsWith('00') || text === '20')) {
        return { ok: true };
      }
      return { ok: false, message: text || 'SMS gönderilemedi.' };
    } catch (e) {
      const err = e as Error;
      return { ok: false, message: err.message || 'SMS servisi hatası.' };
    }
  }
}
