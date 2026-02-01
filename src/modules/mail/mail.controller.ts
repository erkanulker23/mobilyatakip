import { Controller, Get, Post, Body, Param, Query } from '@nestjs/common';
import { MailService } from './mail.service';

@Controller('mail')
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
}
