import { Controller, Get, Post, Delete, Body, Param, Query, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { KasaHareketService } from './kasa-hareket.service';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../../entities/user.entity';
import { UserRole } from '../../common/enums/role.enum';

@Controller('kasa-hareket')
@UseGuards(AuthGuard('jwt'), RolesGuard)
@Roles(UserRole.ADMIN, UserRole.MUHASEBE)
export class KasaHareketController {
  constructor(private readonly service: KasaHareketService) {}

  @Get()
  list(
    @Query('kasaId') kasaId: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    if (!kasaId) return Promise.resolve([]);
    return this.service.findAllByKasa(kasaId, from, to);
  }

  @Post('giris')
  giris(
    @Body() body: { kasaId: string; amount: number; movementDate: string; description?: string },
    @CurrentUser() user: User,
  ) {
    return this.service.giris({ ...body, userId: user?.id });
  }

  @Post('cikis')
  cikis(
    @Body() body: { kasaId: string; amount: number; movementDate: string; description?: string },
    @CurrentUser() user: User,
  ) {
    return this.service.cikis({ ...body, userId: user?.id });
  }

  @Post('virman')
  virman(
    @Body()
    body: {
      fromKasaId: string;
      toKasaId: string;
      amount: number;
      movementDate: string;
      description?: string;
    },
    @CurrentUser() user: User,
  ) {
    return this.service.virman({ ...body, userId: user?.id });
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.service.findOne(id);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.service.remove(id);
  }
}
