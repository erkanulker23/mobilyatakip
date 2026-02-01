import { Injectable } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { createConnection, type Connection } from 'mysql2/promise';
import { DataSource } from 'typeorm';
import { join } from 'path';
import { existsSync, writeFileSync, readFileSync } from 'fs';
import { spawn } from 'child_process';

export interface CheckDbResult {
  ok: boolean;
  message?: string;
  created?: boolean;
}

export interface SaveConfigResult {
  ok: boolean;
  message?: string;
}

export interface RunSchemaResult {
  ok: boolean;
  message?: string;
}

export interface BuildFrontendResult {
  ok: boolean;
  message?: string;
  log?: string;
}

@Injectable()
export class InstallService {
  constructor(private readonly config: ConfigService) {}

  isInstallMode(): boolean {
    return this.config.get<string>('INSTALL_MODE') === '1';
  }

  async checkDb(params: {
    host: string;
    port?: number;
    username: string;
    password?: string;
    database: string;
    createDatabase?: boolean;
  }): Promise<CheckDbResult> {
    const port = params.port ?? 3306;
    let conn: Connection | null = null;
    try {
      conn = await createConnection({
        host: params.host,
        port,
        user: params.username,
        password: params.password || '',
        multipleStatements: true,
      });
      await conn.ping();

      const [rows] = await conn.query(
        'SELECT 1 as ok FROM information_schema.schemata WHERE schema_name = ?',
        [params.database],
      );
      const exists = Array.isArray(rows) && rows.length > 0;

      if (!exists && params.createDatabase) {
        await conn.query(`CREATE DATABASE \`${params.database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
        await conn.end();
        return { ok: true, created: true };
      }
      if (!exists) {
        await conn.end();
        return { ok: false, message: `Veritabanı "${params.database}" bulunamadı. Oluşturulsun mu?` };
      }
      await conn.end();
      return { ok: true };
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : String(err);
      if (conn) try { await conn.end(); } catch { /* ignore */ }
      return { ok: false, message };
    }
  }

  async saveConfig(config: Record<string, string | number | undefined>): Promise<SaveConfigResult> {
    if (!this.isInstallMode()) {
      return { ok: false, message: 'Kurulum modu kapalı.' };
    }
    const envPath = join(process.cwd(), '.env');
    const lines: string[] = [
      '# Mobilya Takip - .env (kurulum sihirbazı ile oluşturuldu)',
      'INSTALL_MODE=0',
      '',
      '# Database (MySQL)',
      `DB_CONNECTION=mysql`,
      `DB_HOST=${config.DB_HOST ?? '127.0.0.1'}`,
      `DB_PORT=${config.DB_PORT ?? 3306}`,
      `DB_USERNAME=${config.DB_USERNAME ?? 'root'}`,
      `DB_PASSWORD=${config.DB_PASSWORD ?? ''}`,
      `DB_DATABASE=${config.DB_DATABASE ?? 'mobilyatakip'}`,
      '',
      '# JWT',
      `JWT_SECRET=${config.JWT_SECRET ?? 'change-me-in-production'}`,
      `JWT_EXPIRES_IN=${config.JWT_EXPIRES_IN ?? '1d'}`,
      '',
      '# Mail (opsiyonel)',
      `MAIL_HOST=${config.MAIL_HOST ?? ''}`,
      `MAIL_PORT=${config.MAIL_PORT ?? 587}`,
      `MAIL_USER=${config.MAIL_USER ?? ''}`,
      `MAIL_PASSWORD=${config.MAIL_PASSWORD ?? ''}`,
      `MAIL_FROM=${config.MAIL_FROM ?? 'Mobilya Takip <noreply@example.com>'}`,
      '',
      '# Application',
      `NODE_ENV=production`,
      `PORT=${config.PORT ?? 3001}`,
      `APP_URL=${(config.APP_URL ?? '').toString().replace(/\/$/, '')}`,
      `FRONTEND_URL=${(config.FRONTEND_URL ?? config.APP_URL ?? '').toString().replace(/\/$/, '')}`,
    ];
    if (config.CORS_ORIGINS) {
      lines.push(`CORS_ORIGINS=${config.CORS_ORIGINS}`);
    }
    try {
      writeFileSync(envPath, lines.join('\n'), 'utf8');
      return { ok: true };
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : String(err);
      return { ok: false, message };
    }
  }

  private loadEnv(): Record<string, string> {
    const envPath = join(process.cwd(), '.env');
    if (!existsSync(envPath)) return {};
    const content = readFileSync(envPath, 'utf8');
    const out: Record<string, string> = {};
    for (const line of content.split('\n')) {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith('#')) continue;
      const eq = trimmed.indexOf('=');
      if (eq === -1) continue;
      const key = trimmed.slice(0, eq).trim();
      let val = trimmed.slice(eq + 1).trim();
      if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
        val = val.slice(1, -1);
      }
      out[key] = val;
    }
    return out;
  }

  async runSchema(): Promise<RunSchemaResult> {
    if (!this.isInstallMode()) {
      return { ok: false, message: 'Kurulum modu kapalı.' };
    }
    const env = this.loadEnv();
    const entitiesPath = join(process.cwd(), 'dist', 'entities', '**', '*.entity.js');
    const dataSource = new DataSource({
      type: 'mysql',
      host: env.DB_HOST || 'localhost',
      port: parseInt(env.DB_PORT || '3306', 10),
      username: env.DB_USERNAME || 'root',
      password: env.DB_PASSWORD || '',
      database: env.DB_DATABASE || 'mobilyatakip',
      entities: [entitiesPath],
      synchronize: true,
      logging: false,
      charset: 'utf8mb4',
    });
    try {
      await dataSource.initialize();
      await dataSource.synchronize();
      await dataSource.destroy();
      return { ok: true };
    } catch (err: unknown) {
      try { await dataSource.destroy(); } catch { /* ignore */ }
      const message = err instanceof Error ? err.message : String(err);
      return { ok: false, message };
    }
  }

  async buildFrontend(): Promise<BuildFrontendResult> {
    if (!this.isInstallMode()) {
      return { ok: false, message: 'Kurulum modu kapalı.' };
    }
    return new Promise((resolve) => {
      const frontendDir = join(process.cwd(), 'frontend');
      if (!existsSync(join(frontendDir, 'package.json'))) {
        return resolve({ ok: false, message: 'frontend klasörü bulunamadı.' });
      }
      const log: string[] = [];
      const child = spawn('npm', ['run', 'build'], {
        cwd: frontendDir,
        shell: true,
        stdio: ['ignore', 'pipe', 'pipe'],
      });
      child.stdout?.on('data', (d) => log.push(d.toString()));
      child.stderr?.on('data', (d) => log.push(d.toString()));
      child.on('close', (code) => {
        if (code === 0) {
          resolve({ ok: true, log: log.join('') });
        } else {
          resolve({ ok: false, message: `Build çıkış kodu: ${code}`, log: log.join('') });
        }
      });
      child.on('error', (err) => {
        resolve({ ok: false, message: err.message, log: log.join('') });
      });
    });
  }

  getStatus(): { installMode: boolean; nodeVersion: string; cwd: string } {
    return {
      installMode: this.isInstallMode(),
      nodeVersion: process.version,
      cwd: process.cwd(),
    };
  }
}
