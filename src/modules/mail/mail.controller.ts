import { Controller, Get, Post, Body, Param, Query, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { MailService } from './mail.service';

@Controller('mail')
@UseGuards(AuthGuard('jwt'))
export class MailController {
  constructor(private mail: MailService) {}

  @Post('send')
  send(
    @Body()
    body: {
      to: string;
      cc?: string;
      subject: string;
      text?: string;
      html?: string;
      entityType?: string;
      entityId?: string;
      userId?: string;
    },
  ) {
    return this.mail.send(body);
  }

  @Post('log/:id/read')
  markRead(@Param('id') id: string) {
    return this.mail.markRead(id);
  }

  @Get('logs')
  logs(@Query('entityType') entityType?: string, @Query('entityId') entityId?: string) {
    return this.mail.getLogs({ entityType, entityId });
  }

  @Post('test')
  test(@Body() body: { to: string }) {
    const to = body?.to?.trim();
    if (!to) return Promise.resolve({ ok: false, message: 'E-posta adresi gerekli.' });
    return this.mail.sendTest(to);
  }
}
