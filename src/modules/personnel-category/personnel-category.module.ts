import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { PersonnelCategory } from '../../entities/personnel-category.entity';
import { PersonnelCategoryService } from './personnel-category.service';
import { PersonnelCategoryController } from './personnel-category.controller';

@Module({
  imports: [TypeOrmModule.forFeature([PersonnelCategory])],
  providers: [PersonnelCategoryService],
  controllers: [PersonnelCategoryController],
  exports: [PersonnelCategoryService],
})
export class PersonnelCategoryModule {}
