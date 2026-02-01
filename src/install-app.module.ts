import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { InstallModule } from './modules/install/install.module';

/** Kurulum modunda kullanılan minimal modül (TypeORM yok, sadece Install API) */
@Module({
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    InstallModule,
  ],
})
export class InstallAppModule {}
