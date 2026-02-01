import { NestFactory } from '@nestjs/core';
import { ValidationPipe, RequestMethod } from '@nestjs/common';
import { DocumentBuilder, SwaggerModule } from '@nestjs/swagger';
import { join } from 'path';
import { existsSync, mkdirSync, copyFileSync, appendFileSync } from 'fs';
import helmet from 'helmet';
import { config as dotenvConfig } from 'dotenv';
import { AppModule } from './app.module';
import { InstallAppModule } from './install-app.module';

const rootDir = join(__dirname, '..');

function ensureEnv(): void {
  const envPath = join(rootDir, '.env');
  const examplePath = join(rootDir, '.env.example');
  const installCompletePath = join(rootDir, '.install_complete');
  if (!existsSync(envPath) && existsSync(examplePath)) {
    copyFileSync(examplePath, envPath);
    appendFileSync(envPath, '\nINSTALL_MODE=1\n');
  }
  dotenvConfig({ path: envPath });
  // Kurulum tamamlandı işareti varsa her zaman normal modda çalış
  if (existsSync(installCompletePath)) {
    process.env.INSTALL_MODE = '0';
  }
}

async function bootstrapInstall(): Promise<void> {
  const app = await NestFactory.create(InstallAppModule);
  app.setGlobalPrefix('api', {
    exclude: [{ path: '', method: RequestMethod.GET }, { path: 'install.html', method: RequestMethod.GET }],
  });
  app.use(helmet({ contentSecurityPolicy: false }));
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: true,
      transform: true,
      transformOptions: { enableImplicitConversion: true },
    }),
  );
  app.enableCors({ origin: true, credentials: true });
  const express = app.getHttpAdapter().getInstance();
  const { default: expressStatic } = await import('express').then((m) => ({ default: m.static }));
  const publicDir = join(rootDir, 'public');
  if (existsSync(publicDir)) {
    express.use(expressStatic(publicDir));
  }
  const installHtml = join(rootDir, 'public', 'install.html');
  express.get('/', (_req: unknown, res: { sendFile: (p: string) => void; setHeader: (n: string, v: string) => void; send: (b: string) => void }) => {
    if (existsSync(installHtml)) {
      res.sendFile(installHtml);
    } else {
      res.setHeader('Content-Type', 'text/html; charset=utf-8');
      res.send(installFallbackHtml());
    }
  });
  const port = parseInt(process.env.PORT || '3001', 10);
  await app.listen(port, '0.0.0.0');
  console.log(`Mobilya Takip Kurulum Sihirbazı: http://localhost:${port}`);
}

function installFallbackHtml(): string {
  return `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Kurulum</title></head><body>
<h1>Kurulum Sihirbazı</h1>
<p>public/install.html dosyası bulunamadı. API kullanılabilir:</p>
<ul>
<li><a href="/api/install/status">GET /api/install/status</a></li>
<li>POST /api/install/check-db</li>
<li>POST /api/install/save-config</li>
<li>POST /api/install/run-schema</li>
<li>POST /api/install/build-frontend</li>
</ul>
</body></html>`;
}

async function bootstrap() {
  ensureEnv();
  if (process.env.INSTALL_MODE === '1') {
    return bootstrapInstall();
  }

  const uploadsDir = join(__dirname, '..', 'uploads');
  if (!existsSync(uploadsDir)) {
    mkdirSync(uploadsDir, { recursive: true });
  }

  const app = await NestFactory.create(AppModule);
  app.use(helmet({ contentSecurityPolicy: process.env.NODE_ENV === 'production' }));
  app.setGlobalPrefix('api', {
    exclude: [{ path: '', method: RequestMethod.GET }],
  });
  const express = app.getHttpAdapter().getInstance();
  const { default: expressStatic } = await import('express').then((m) => ({ default: m.static }));
  express.use('/uploads', expressStatic(uploadsDir));
  const frontendDist = join(__dirname, '..', 'frontend', 'dist');
  const frontendUrl = (process.env.FRONTEND_URL || 'http://localhost:5174').replace(/\/$/, '');
  const apiPathPrefixes = ['/api', '/uploads', '/docs'];
  if (existsSync(frontendDist)) {
    express.use(expressStatic(frontendDist));
    express.get('*', (req: { path: string }, res: { sendFile: (p: string) => void }, next: () => void) => {
      const isApi = apiPathPrefixes.some((p) => req.path === p || req.path.startsWith(p + '/'));
      if (isApi) return next();
      res.sendFile(join(frontendDist, 'index.html'));
    });
  }
  // GET / (root) is handled by RootController (redirect to frontend when no build)
  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,
      forbidNonWhitelisted: true,
      transform: true,
      transformOptions: { enableImplicitConversion: true },
    }),
  );
  const port = parseInt(process.env.PORT || '3001', 10);
  const appUrl = (process.env.APP_URL || `http://localhost:${port}`).replace(/\/$/, '');
  const corsOrigins = process.env.CORS_ORIGINS
    ? process.env.CORS_ORIGINS.split(',').map((o) => o.trim()).filter(Boolean)
    : [appUrl, frontendUrl, 'http://mobilyatakip-v1.test', 'https://mobilyatakip-v1.test', 'http://localhost:3001', 'http://localhost:5174'];
  app.enableCors({
    origin: corsOrigins,
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization'],
  });
  const config = new DocumentBuilder()
    .setTitle('Mobilya Takip API')
    .setDescription('Stok, Satış, Teklif, Tedarikçi, SSH, Cari Yönetimi REST API')
    .setVersion('1.0')
    .addBearerAuth()
    .build();
  const document = SwaggerModule.createDocument(app, config);
  SwaggerModule.setup('docs', app, document);
  await app.listen(port, '0.0.0.0');
  console.log(`Mobilya Takip API: ${appUrl} (port ${port})`);
  console.log(`Swagger: ${appUrl}/api/docs`); // Swagger path is under global prefix
  if (!existsSync(frontendDist)) {
    console.log(`GET / → ${frontendUrl}`);
  }
}
bootstrap();
