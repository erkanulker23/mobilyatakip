import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { InstallController } from './install.controller';
import { InstallService } from './install.service';

@Module({
  imports: [ConfigModule.forRoot({ isGlobal: true })],
  controllers: [InstallController],
  providers: [InstallService],
})
export class InstallModule {}
