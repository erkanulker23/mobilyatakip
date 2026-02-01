import { Controller, Get, Post, Body, Req, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { PaymentService } from './payment.service';

@Controller('payments')
@UseGuards(AuthGuard('jwt'))
export class PaymentController {
  constructor(private payment: PaymentService) {}

  @Get('paytr-config')
  async paytrConfig() {
    const active = await this.payment.isPayTrActive();
    return { paytrActive: active };
  }

  @Post('paytr-token')
  async paytrToken(
    @Body()
    body: {
      merchantOid: string;
      amountKurus: number;
      customerEmail: string;
      customerName: string;
      successUrl?: string;
      failUrl?: string;
      callbackUrl?: string;
    },
    @Req() req: { ip?: string; headers?: { 'x-forwarded-for'?: string; 'x-real-ip'?: string } },
  ) {
    const userIp =
      (req.headers?.['x-forwarded-for'] as string)?.split(',')[0]?.trim() ||
      req.headers?.['x-real-ip'] ||
      req.ip ||
      '127.0.0.1';
    return this.payment.getPayTrToken({
      ...body,
      userIp: String(userIp),
    });
  }
}
