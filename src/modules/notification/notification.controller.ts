import { Controller, Get, Post, Body, Param, Query } from '@nestjs/common';
import { NotificationService } from './notification.service';

@Controller('notifications')
export class NotificationController {
  constructor(private notification: NotificationService) {}

  @Get('user/:userId')
  byUser(@Param('userId') userId: string, @Query('unreadOnly') unreadOnly?: string) {
    return this.notification.findByUser(userId, unreadOnly === 'true');
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.notification.create(body as any);
  }

  @Post(':id/read')
  markRead(@Param('id') id: string) {
    return this.notification.markRead(id);
  }

  @Post('user/:userId/read-all')
  markAllRead(@Param('userId') userId: string) {
    return this.notification.markAllRead(userId);
  }
}
