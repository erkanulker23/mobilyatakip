import { Controller, Get, Post, Delete, Body, Param, Query } from '@nestjs/common';
import { XmlFeedService } from './xml-feed.service';

@Controller('xml-feeds')
export class XmlFeedController {
  constructor(private readonly xmlFeedService: XmlFeedService) {}

  @Get()
  list() {
    return this.xmlFeedService.findAll();
  }

  @Post()
  create(@Body() body: { name: string; url: string; supplierId?: string | null }) {
    return this.xmlFeedService.create(body);
  }

  @Delete(':id')
  remove(@Param('id') id: string, @Query('deleteProducts') deleteProducts?: string) {
    return this.xmlFeedService.remove(id, deleteProducts === 'true' || deleteProducts === '1');
  }
}
