import { Controller, Get, Post, Body, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { SmsService } from './sms.service';

@Controller('sms')
@UseGuards(AuthGuard('jwt'))
export class SmsController {
  constructor(private sms: SmsService) {}

  @Get('config')
  async config() {
    const configured = await this.sms.isConfigured();
    return { smsConfigured: configured };
  }

  @Post('test')
  async test(@Body() body: { phone: string; message?: string }) {
    const phone = body?.phone?.trim();
    if (!phone) return { ok: false, message: 'Telefon numarası gerekli.' };
    const message = body?.message?.trim() || 'Mobilya Takip test SMS.';
    return this.sms.send(phone, message);
  }
}
