import { Body, Controller, Get, Post } from '@nestjs/common';
import { InstallService } from './install.service';
import { CheckDbDto } from './dto/check-db.dto';
import { SaveConfigDto } from './dto/save-config.dto';

@Controller('install')
export class InstallController {
  constructor(private readonly installService: InstallService) {}

  @Get('status')
  getStatus() {
    return this.installService.getStatus();
  }

  @Post('check-db')
  async checkDb(@Body() dto: CheckDbDto) {
    return this.installService.checkDb({
      host: dto.host,
      port: dto.port,
      username: dto.username,
      password: dto.password,
      database: dto.database,
      createDatabase: dto.createDatabase,
    });
  }

  @Post('save-config')
  async saveConfig(@Body() dto: SaveConfigDto) {
    return this.installService.saveConfig({
      DB_HOST: dto.DB_HOST,
      DB_PORT: dto.DB_PORT ?? 3306,
      DB_USERNAME: dto.DB_USERNAME,
      DB_PASSWORD: dto.DB_PASSWORD,
      DB_DATABASE: dto.DB_DATABASE,
      JWT_SECRET: dto.JWT_SECRET,
      JWT_EXPIRES_IN: dto.JWT_EXPIRES_IN ?? '1d',
      MAIL_HOST: dto.MAIL_HOST,
      MAIL_PORT: dto.MAIL_PORT,
      MAIL_USER: dto.MAIL_USER,
      MAIL_PASSWORD: dto.MAIL_PASSWORD,
      MAIL_FROM: dto.MAIL_FROM,
      PORT: dto.PORT ?? 3001,
      APP_URL: dto.APP_URL,
      FRONTEND_URL: dto.FRONTEND_URL ?? dto.APP_URL,
      CORS_ORIGINS: dto.CORS_ORIGINS,
    });
  }

  @Post('run-schema')
  async runSchema() {
    return this.installService.runSchema();
  }

  @Post('build-frontend')
  async buildFrontend() {
    return this.installService.buildFrontend();
  }
}
