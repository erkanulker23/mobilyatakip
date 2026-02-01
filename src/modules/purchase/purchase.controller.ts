import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { PurchaseService, CreatePurchaseDto } from './purchase.service';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { UserRole } from '../../common/enums/role.enum';

@Controller('purchases')
export class PurchaseController {
  constructor(private purchase: PurchaseService) {}

  @Get()
  list(
    @Query('supplierId') supplierId?: string,
    @Query('dateFrom') dateFrom?: string,
    @Query('dateTo') dateTo?: string,
    @Query('purchaseNumber') purchaseNumber?: string,
    @Query('isReturn') isReturn?: string,
    @Query('page') page?: string,
    @Query('limit') limit?: string,
  ) {
    return this.purchase.findAll({
      supplierId,
      dateFrom,
      dateTo,
      purchaseNumber,
      isReturn: isReturn === 'true' ? true : isReturn === 'false' ? false : undefined,
      page: page ? Number(page) : undefined,
      limit: limit ? Number(limit) : undefined,
    });
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.purchase.findOne(id);
  }

  @Post()
  create(@Body() body: CreatePurchaseDto & { warehouseId: string }) {
    const { warehouseId, ...dto } = body;
    return this.purchase.create(dto, warehouseId);
  }

  @Put(':id')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN)
  update(@Param('id') id: string, @Body() body: { purchaseDate?: string; dueDate?: string; notes?: string; paidAmount?: number }) {
    return this.purchase.update(id, body);
  }

  @Delete(':id')
  @UseGuards(AuthGuard('jwt'), RolesGuard)
  @Roles(UserRole.ADMIN)
  remove(@Param('id') id: string) {
    return this.purchase.remove(id);
  }
}
