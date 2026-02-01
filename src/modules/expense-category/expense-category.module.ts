import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ExpenseCategory } from '../../entities/expense-category.entity';
import { ExpenseCategoryService } from './expense-category.service';
import { ExpenseCategoryController } from './expense-category.controller';

@Module({
  imports: [TypeOrmModule.forFeature([ExpenseCategory])],
  providers: [ExpenseCategoryService],
  controllers: [ExpenseCategoryController],
  exports: [ExpenseCategoryService],
})
export class ExpenseCategoryModule {}
