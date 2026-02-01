import { BadRequestException, Injectable } from '@nestjs/common';
import { createHmac } from 'crypto';
import { CompanyService } from '../company/company.service';

@Injectable()
export class PaymentService {
  constructor(private company: CompanyService) {}

  async isPayTrActive(): Promise<boolean> {
    const company = await this.company.findOne();
    return !!(company?.paytrMerchantId && company?.paytrMerchantKey && company?.paytrMerchantSalt);
  }

  async getPayTrToken(data: {
    merchantOid: string;
    amountKurus: number;
    customerEmail: string;
    customerName: string;
    userIp: string;
    successUrl?: string;
    failUrl?: string;
    callbackUrl?: string;
  }): Promise<{ token: string; iframeUrl: string }> {
    const company = await this.company.findOne();
    if (!company?.paytrMerchantId || !company?.paytrMerchantKey || !company?.paytrMerchantSalt) {
      throw new BadRequestException('PayTR ayarları eksik. Ayarlar > Şirket bölümünden PayTR bilgilerini girin.');
    }
    const userBasket = Buffer.from(
      JSON.stringify([[data.customerName, (data.amountKurus / 100).toFixed(2), 1]]),
      'utf-8',
    ).toString('base64');
    const noInstallment = '0';
    const maxInstallment = '0';
    const currency = 'TL';
    const testMode = company.paytrTestMode ? '1' : '0';
    const hashStr =
      company.paytrMerchantId +
      data.userIp +
      data.merchantOid +
      data.customerEmail +
      String(data.amountKurus) +
      userBasket +
      noInstallment +
      maxInstallment +
      currency +
      testMode +
      company.paytrMerchantSalt;
    const token = createHmac('sha256', company.paytrMerchantKey).update(hashStr).digest('base64');
    const form = new URLSearchParams({
      merchant_id: company.paytrMerchantId,
      user_ip: data.userIp,
      merchant_oid: data.merchantOid,
      email: data.customerEmail,
      payment_amount: String(data.amountKurus),
      paytr_token: token,
      user_basket: userBasket,
      debug_on: testMode,
      no_installment: noInstallment,
      max_installment: maxInstallment,
      user_name: data.customerName,
      user_address: 'Adres',
      user_phone: '05000000000',
      merchant_ok_url: data.successUrl || '',
      merchant_fail_url: data.failUrl || '',
      timeout_limit: '30',
      currency: currency,
    });
    const res = await fetch('https://www.paytr.com/odeme/api/get-token', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: form.toString(),
    });
    const text = await res.text();
    const json = JSON.parse(text) as { status?: string; token?: string; reason?: string };
    if (json.status !== 'success' || !json.token) {
      throw new BadRequestException(json.reason || 'PayTR token alınamadı.');
    }
    const iframeUrl = 'https://www.paytr.com/odeme/guvenli';
    return { token: json.token, iframeUrl };
  }
}
