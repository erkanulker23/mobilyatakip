import { IsString, IsOptional, IsNumber, Min, Max } from 'class-validator';
import { Transform } from 'class-transformer';

const toNumber = (v: unknown) => (v === '' || v === undefined ? undefined : Number(v));

export class SaveConfigDto {
  @IsString()
  DB_HOST: string;

  @IsOptional()
  @Transform(({ value }) => toNumber(value))
  @IsNumber()
  @Min(1)
  @Max(65535)
  DB_PORT?: number;

  @IsString()
  DB_USERNAME: string;

  @IsOptional()
  @IsString()
  DB_PASSWORD?: string;

  @IsString()
  DB_DATABASE: string;

  @IsString()
  JWT_SECRET: string;

  @IsOptional()
  @IsString()
  JWT_EXPIRES_IN?: string;

  @IsOptional()
  @IsString()
  MAIL_HOST?: string;

  @IsOptional()
  @Transform(({ value }) => toNumber(value))
  @IsNumber()
  @Min(1)
  @Max(65535)
  MAIL_PORT?: number;

  @IsOptional()
  @IsString()
  MAIL_USER?: string;

  @IsOptional()
  @IsString()
  MAIL_PASSWORD?: string;

  @IsOptional()
  @IsString()
  MAIL_FROM?: string;

  @IsOptional()
  @Transform(({ value }) => toNumber(value))
  @IsNumber()
  @Min(1)
  @Max(65535)
  PORT?: number;

  @IsString()
  APP_URL: string;

  @IsOptional()
  @IsString()
  FRONTEND_URL?: string;

  @IsOptional()
  @IsString()
  CORS_ORIGINS?: string;
}
