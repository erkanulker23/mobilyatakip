import { Controller, Get } from '@nestjs/common';
import { DemoSeedService } from './demo.seed';

@Controller('seed')
export class SeedController {
  constructor(private demoSeed: DemoSeedService) {}

  @Get('demo')
  async runDemo() {
    return this.demoSeed.runDemoSeed();
  }
}
