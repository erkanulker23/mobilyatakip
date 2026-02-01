import { Injectable } from '@nestjs/common';
import PDFDocument from 'pdfkit';
import { createWriteStream, mkdirSync, existsSync } from 'fs';
import { join } from 'path';
import { CompanyService } from '../company/company.service';
import { Quote } from '../../entities/quote.entity';

@Injectable()
export class QuotePdfService {
  constructor(private companyService: CompanyService) {}

  async generate(quote: Quote, outputPath?: string): Promise<Buffer> {
    const company = await this.companyService.findOne();
    const chunks: Buffer[] = [];
    const doc = new PDFDocument({ size: 'A4', margin: 50 });

    doc.on('data', (chunk: Buffer) => chunks.push(chunk));

    if (company?.logoUrl) {
      try {
        const logoPath = company.logoUrl.startsWith('/') ? join(process.cwd(), 'public', company.logoUrl.slice(1)) : join(process.cwd(), company.logoUrl);
        doc.image(logoPath, 50, 50, { width: 80 });
        doc.y = 140;
      } catch {
        // Logo yüklenemezse yalnızca metin başlık kullanılır
      }
    }
    doc.fontSize(18).text(company?.name || 'Firma Adı', { align: 'center' });
    doc.moveDown(0.5);
    if (company?.address) doc.fontSize(10).text(company.address, { align: 'center' });
    if (company?.phone) doc.text(`Tel: ${company.phone}`, { align: 'center' });
    if (company?.email) doc.text(company.email, { align: 'center' });
    doc.moveDown(1);

    doc.fontSize(14).text(`TEKLİF - ${quote.quoteNumber} (Rev. v${quote.revision})`, { align: 'center' });
    doc.moveDown(0.5);
    doc.fontSize(10);
    doc.text(`Müşteri: ${(quote as any).customer?.name || '-'}`);
    doc.text(`Tarih: ${new Date(quote.createdAt).toLocaleDateString('tr-TR')}`);
    if (quote.validUntil) doc.text(`Geçerlilik: ${new Date(quote.validUntil).toLocaleDateString('tr-TR')}`);
    doc.moveDown(1);

    const tableTop = doc.y;
    doc.font('Helvetica-Bold');
    doc.fontSize(9);
    doc.text('Ürün', 50, tableTop);
    doc.text('Birim Fiyat', 250, tableTop);
    doc.text('Adet', 320, tableTop);
    doc.text('İndirim', 360, tableTop);
    doc.text('Satır Toplam', 420, tableTop);
    doc.moveDown(0.5);
    doc.moveTo(50, doc.y).lineTo(550, doc.y).stroke();
    doc.moveDown(0.3);
    doc.font('Helvetica');

    let y = doc.y;
    for (const item of (quote as any).items || []) {
      const productName = String((item.product as any)?.name ?? item.productId ?? '-').substring(0, 35);
      doc.fontSize(9).text(productName, 50, y);
      doc.text(Number(item.unitPrice).toFixed(2), 250, y);
      doc.text(String(item.quantity), 320, y);
      doc.text(`${Number(item.lineDiscountPercent || 0)}%`, 360, y);
      doc.text(Number(item.lineTotal).toFixed(2), 420, y);
      y += 20;
      doc.y = y;
    }

    doc.moveDown(1);
    doc.font('Helvetica-Bold');
    doc.text(`Ara Toplam: ${Number(quote.subtotal).toFixed(2)} TL`, 350, doc.y);
    doc.text(`Genel İndirim: %${quote.generalDiscountPercent} / ${Number(quote.generalDiscountAmount).toFixed(2)} TL`, 350, doc.y + 18);
    doc.text(`KDV Toplam: ${Number(quote.kdvTotal).toFixed(2)} TL`, 350, doc.y + 36);
    doc.text(`GENEL TOPLAM: ${Number(quote.grandTotal).toFixed(2)} TL`, 350, doc.y + 54);
    doc.font('Helvetica');

    if (quote.notes) {
      doc.moveDown(2).fontSize(9).text('Not: ' + quote.notes, 50, doc.y);
    }

    doc.end();

    return new Promise<Buffer>((resolve, reject) => {
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      doc.on('error', reject);
    });
  }

  async saveToFile(quote: Quote, dir = 'uploads/quotes'): Promise<string> {
    const quoteWithRelations = quote as Quote & { customer?: { name: string }; items?: Array<{ product?: { name: string }; unitPrice: number; quantity: number; lineDiscountPercent?: number; lineTotal: number }> };
    const buffer = await this.generate(quoteWithRelations);
    if (!existsSync(dir)) mkdirSync(dir, { recursive: true });
    const filename = `${quote.quoteNumber.replace(/\//g, '-')}_v${quote.revision}.pdf`;
    const filepath = join(dir, filename);
    const stream = createWriteStream(filepath);
    stream.write(buffer);
    stream.end();
    return new Promise((resolve, reject) => {
      stream.on('finish', () => resolve(filepath));
      stream.on('error', reject);
    });
  }
}
