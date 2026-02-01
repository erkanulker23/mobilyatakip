import { Controller, Get, Res } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { Response } from 'express';
import { join } from 'path';
import { existsSync } from 'fs';

@Controller()
export class RootController {
  constructor(private readonly config: ConfigService) {}

  @Get()
  root(@Res() res: Response) {
    const frontendDist = join(__dirname, '..', 'frontend', 'dist');
    if (existsSync(frontendDist)) {
      res.sendFile(join(frontendDist, 'index.html'));
    } else {
      const frontendUrl = (this.config.get<string>('FRONTEND_URL') || 'http://localhost:5174').replace(/\/$/, '');
      res.redirect(302, frontendUrl);
    }
  }
}
