import { Injectable } from '@nestjs/common';
import PDFDocument from 'pdfkit';
import { join } from 'path';
import { CompanyService } from '../company/company.service';
import { Sale } from '../../entities/sale.entity';

@Injectable()
export class SalePdfService {
  constructor(private readonly companyService: CompanyService) {}

  async generate(sale: Sale): Promise<Buffer> {
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

    doc.fontSize(14).text(`SATIŞ FİŞİ - ${sale.saleNumber}`, { align: 'center' });
    doc.moveDown(0.5);
    doc.fontSize(10);
    doc.text(`Müşteri: ${(sale as Sale & { customer?: { name: string } }).customer?.name || '-'}`);
    doc.text(`Tarih: ${new Date(sale.saleDate).toLocaleDateString('tr-TR')}`);
    if (sale.dueDate) doc.text(`Vade: ${new Date(sale.dueDate).toLocaleDateString('tr-TR')}`);
    doc.moveDown(1);

    const tableTop = doc.y;
    doc.font('Helvetica-Bold');
    doc.fontSize(9);
    doc.text('Ürün', 50, tableTop);
    doc.text('Birim Fiyat', 250, tableTop);
    doc.text('Adet', 320, tableTop);
    doc.text('KDV %', 360, tableTop);
    doc.text('Satır Toplam', 420, tableTop);
    doc.moveDown(0.5);
    doc.moveTo(50, doc.y).lineTo(550, doc.y).stroke();
    doc.moveDown(0.3);
    doc.font('Helvetica');

    let y = doc.y;
    for (const item of (sale as any).items || []) {
      const productName = String((item.product as any)?.name ?? item.productId ?? '-').substring(0, 35);
      doc.fontSize(9).text(productName, 50, y);
      doc.text(Number(item.unitPrice).toFixed(2), 250, y);
      doc.text(String(item.quantity), 320, y);
      doc.text(`${Number(item.kdvRate ?? 0)}%`, 360, y);
      doc.text(Number(item.lineTotal).toFixed(2), 420, y);
      y += 20;
      doc.y = y;
    }

    doc.moveDown(1);
    doc.font('Helvetica-Bold');
    doc.text(`Ara Toplam: ${Number(sale.subtotal).toFixed(2)} TL`, 350, doc.y);
    doc.text(`KDV Toplam: ${Number(sale.kdvTotal).toFixed(2)} TL`, 350, doc.y + 18);
    doc.text(`GENEL TOPLAM: ${Number(sale.grandTotal).toFixed(2)} TL`, 350, doc.y + 36);
    doc.text(`Ödenen: ${Number(sale.paidAmount).toFixed(2)} TL`, 350, doc.y + 54);
    doc.font('Helvetica');

    if (sale.notes) {
      doc.moveDown(2).fontSize(9).text('Not: ' + sale.notes, 50, doc.y);
    }

    doc.end();

    return new Promise<Buffer>((resolve, reject) => {
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      doc.on('error', reject);
    });
  }
}
