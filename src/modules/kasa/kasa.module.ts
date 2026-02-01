import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { Kasa } from '../../entities/kasa.entity';
import { KasaService } from './kasa.service';
import { KasaController } from './kasa.controller';
import { AuthModule } from '../auth/auth.module';

@Module({
  imports: [TypeOrmModule.forFeature([Kasa]), AuthModule],
  providers: [KasaService],
  controllers: [KasaController],
  exports: [KasaService],
})
export class KasaModule {}
