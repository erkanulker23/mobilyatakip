import { Controller, Get, Post, Put, Delete, Body, Param, Query } from '@nestjs/common';
import { PersonnelService } from './personnel.service';

@Controller('personnel')
export class PersonnelController {
  constructor(private personnel: PersonnelService) {}

  @Get()
  list(
    @Query('active') active?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.personnel.findAll({
      active: active === 'true' ? true : active === 'false' ? false : undefined,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.personnel.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.personnel.create(body as Partial<import('../../entities/personnel.entity').Personnel>);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.personnel.update(id, body as Partial<import('../../entities/personnel.entity').Personnel>);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.personnel.remove(id);
  }
}
