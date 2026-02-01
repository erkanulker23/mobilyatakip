import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Personnel } from '../../entities/personnel.entity';
import { PersonnelService } from './personnel.service';
import { PersonnelController } from './personnel.controller';

@Module({
  imports: [TypeOrmModule.forFeature([Personnel])],
  providers: [PersonnelService],
  controllers: [PersonnelController],
  exports: [PersonnelService],
})
export class PersonnelModule {}
