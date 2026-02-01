import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { KasaService } from './kasa.service';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/role.enum';

@Controller('kasa')
@UseGuards(AuthGuard('jwt'), RolesGuard)
@Roles(UserRole.ADMIN, UserRole.MUHASEBE)
export class KasaController {
  constructor(private kasa: KasaService) {}

  @Get()
  list(@Query('active') active?: string) {
    return this.kasa.findAll(active === 'true' ? true : active === 'false' ? false : undefined);
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.kasa.findOne(id);
  }

  @Post()
  create(@Body() body: Record<string, unknown>) {
    return this.kasa.create(body as Partial<import('../../entities/kasa.entity').Kasa>);
  }

  @Put(':id')
  @Roles(UserRole.ADMIN)
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.kasa.update(id, body as Partial<import('../../entities/kasa.entity').Kasa>);
  }

  @Delete(':id')
  @Roles(UserRole.ADMIN)
  remove(@Param('id') id: string) {
    return this.kasa.remove(id);
  }
}
