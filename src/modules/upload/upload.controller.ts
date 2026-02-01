import { Controller, Post, UseInterceptors, UploadedFiles, BadRequestException, UseGuards } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';
import { FilesInterceptor } from '@nestjs/platform-express';
import { diskStorage } from 'multer';
import { extname, join } from 'path';

const uploadsDir = join(process.cwd(), 'uploads');

function uniqueFilename(ext: string): string {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}${ext}`;
}

export const multerConfig = {
  storage: diskStorage({
    destination: (_req, _file, cb) => cb(null, uploadsDir),
    filename: (_req, file, cb) => {
      const ext = extname(file.originalname) || '.jpg';
      cb(null, uniqueFilename(ext));
    },
  }),
  limits: { fileSize: 10 * 1024 * 1024 },
  fileFilter: (_req: unknown, file: { mimetype: string }, cb: (err: Error | null, acceptFile: boolean) => void) => {
    const allowed = /^image\/(jpeg|png|gif|webp)$/i.test(file.mimetype);
    if (allowed) cb(null, true);
    else cb(new BadRequestException('Sadece resim dosyaları (jpeg, png, gif, webp) kabul edilir') as Error, false);
  },
};

@Controller('upload')
export class UploadController {
  @Post('images')
  @UseGuards(AuthGuard('jwt'))
  @UseInterceptors(FilesInterceptor('files', 10, multerConfig))
  uploadImages(@UploadedFiles() files: Express.Multer.File[]) {
    if (!files?.length) throw new BadRequestException('En az bir dosya yükleyin');
    const urls = files.map((f) => `/uploads/${f.filename}`);
    return { urls };
  }
}
