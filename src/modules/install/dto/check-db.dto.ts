import { IsString, IsOptional, IsNumber, Min, Max } from 'class-validator';
import { Transform } from 'class-transformer';

const toNumber = (v: unknown) => (v === '' || v === undefined ? undefined : Number(v));

export class CheckDbDto {
  @IsString()
  host: string;

  @IsOptional()
  @Transform(({ value }) => toNumber(value))
  @IsNumber()
  @Min(1)
  @Max(65535)
  port?: number;

  @IsString()
  username: string;

  @IsOptional()
  @IsString()
  password?: string;

  @IsString()
  database: string;

  /** Veritabanı yoksa oluşturulsun mu */
  @IsOptional()
  createDatabase?: boolean;
}
