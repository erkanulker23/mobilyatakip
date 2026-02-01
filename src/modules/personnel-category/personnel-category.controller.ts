import { Controller, Get, Post, Body, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { PersonnelCategoryService } from './personnel-category.service';

@Controller('personnel-categories')
@UseGuards(AuthGuard('jwt'))
export class PersonnelCategoryController {
  constructor(private service: PersonnelCategoryService) {}

  @Get()
  list() {
    return this.service.findAll();
  }

  @Post()
  create(@Body() body: { name: string }) {
    return this.service.create(body);
  }
}
