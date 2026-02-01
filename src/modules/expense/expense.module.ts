import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Expense } from '../../entities/expense.entity';
import { ExpenseService } from './expense.service';
import { ExpenseController } from './expense.controller';
import { AuthModule } from '../auth/auth.module';

@Module({
  imports: [TypeOrmModule.forFeature([Expense]), AuthModule],
  providers: [ExpenseService],
  controllers: [ExpenseController],
  exports: [ExpenseService],
})
export class ExpenseModule {}
