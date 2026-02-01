import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { KasaHareket } from '../../entities/kasa-hareket.entity';
import { KasaHareketService } from './kasa-hareket.service';
import { KasaHareketController } from './kasa-hareket.controller';
import { AuthModule } from '../auth/auth.module';

@Module({
  imports: [TypeOrmModule.forFeature([KasaHareket]), AuthModule],
  providers: [KasaHareketService],
  controllers: [KasaHareketController],
  exports: [KasaHareketService],
})
export class KasaHareketModule {}
