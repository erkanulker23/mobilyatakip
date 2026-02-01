import { Controller, Get, Post, Body, Param, Query } from '@nestjs/common';
import { AuditLogService } from './audit-log.service';

@Controller('audit-logs')
export class AuditLogController {
  constructor(private audit: AuditLogService) {}

  @Get('entity/:entity')
  byEntity(@Param('entity') entity: string, @Query('entityId') entityId?: string, @Query('limit') limit?: string) {
    return this.audit.findByEntity(entity, entityId, limit ? parseInt(limit, 10) : 100);
  }

  @Get('user/:userId')
  byUser(@Param('userId') userId: string, @Query('limit') limit?: string) {
    return this.audit.findByUser(userId, limit ? parseInt(limit, 10) : 100);
  }

  @Post()
  log(@Body() body: Record<string, unknown>) {
    return this.audit.log(body as any);
  }
}
