import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { ExpenseService } from './expense.service';
import { RolesGuard } from '../auth/guards/roles.guard';
import { Roles } from '../auth/decorators/roles.decorator';
import { CurrentUser } from '../auth/decorators/current-user.decorator';
import { User } from '../../entities/user.entity';
import { UserRole } from '../../common/enums/role.enum';

@Controller('expenses')
@UseGuards(AuthGuard('jwt'), RolesGuard)
@Roles(UserRole.ADMIN, UserRole.MUHASEBE)
export class ExpenseController {
  constructor(private expense: ExpenseService) {}

  @Get()
  list(
    @Query('kasaId') kasaId?: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    return this.expense.findAll({ kasaId, from, to });
  }

  @Get(':id')
  get(@Param('id') id: string) {
    return this.expense.findOne(id);
  }

  @Post()
  create(
    @Body() body: Record<string, unknown>,
    @CurrentUser() user: User,
  ) {
    const payload = { ...body, createdBy: user?.id } as Partial<import('../../entities/expense.entity').Expense>;
    return this.expense.create(payload);
  }

  @Put(':id')
  update(@Param('id') id: string, @Body() body: Record<string, unknown>) {
    return this.expense.update(id, body as Partial<import('../../entities/expense.entity').Expense>);
  }

  @Delete(':id')
  remove(@Param('id') id: string) {
    return this.expense.remove(id);
  }
}
