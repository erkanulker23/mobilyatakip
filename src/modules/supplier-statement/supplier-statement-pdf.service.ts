import { Injectable } from '@nestjs/common';
import PDFDocument from 'pdfkit';
import { CompanyService } from '../company/company.service';
import { SupplierStatement } from '../../entities/supplier-statement.entity';
import { Supplier } from '../../entities/supplier.entity';

@Injectable()
export class SupplierStatementPdfService {
  constructor(private readonly companyService: CompanyService) {}

  async generate(statement: SupplierStatement & { supplier?: Supplier }): Promise<Buffer> {
    const company = await this.companyService.findOne();
    const chunks: Buffer[] = [];
    const doc = new PDFDocument({ size: 'A4', margin: 50 });

    doc.on('data', (chunk: Buffer) => chunks.push(chunk));

    doc.fontSize(18).text(company?.name || 'Firma Adı', { align: 'center' });
    doc.moveDown(0.5);
    if (company?.address) doc.fontSize(10).text(company.address, { align: 'center' });
    if (company?.phone) doc.text(`Tel: ${company.phone}`, { align: 'center' });
    if (company?.email) doc.text(company.email, { align: 'center' });
    doc.moveDown(1);

    doc.fontSize(14).text('TEDARİKÇİ MUTABAKAT DÖKÜMÜ', { align: 'center' });
    doc.moveDown(0.5);
    const supplierName = (statement.supplier as Supplier)?.name ?? 'Tedarikçi';
    doc.fontSize(10);
    doc.text(`Tedarikçi: ${supplierName}`);
    doc.text(`Dönem: ${new Date(statement.startDate).toLocaleDateString('tr-TR')} - ${new Date(statement.endDate).toLocaleDateString('tr-TR')}`);
    doc.moveDown(1);

    doc.font('Helvetica-Bold');
    doc.fontSize(10);
    doc.text('Açılış bakiyesi:', 50, doc.y);
    doc.text(Number(statement.openingBalance).toFixed(2) + ' TL', 350, doc.y);
    doc.moveDown(0.5);
    doc.font('Helvetica');
    doc.text('Dönem alımları:', 50, doc.y);
    doc.text(Number(statement.totalPurchases).toFixed(2) + ' TL', 350, doc.y);
    doc.moveDown(0.5);
    doc.text('Dönem ödemeleri:', 50, doc.y);
    doc.text(Number(statement.totalPayments).toFixed(2) + ' TL', 350, doc.y);
    doc.moveDown(0.5);
    doc.font('Helvetica-Bold');
    doc.text('Kapanış bakiyesi:', 50, doc.y);
    doc.text(Number(statement.closingBalance).toFixed(2) + ' TL', 350, doc.y);
    doc.font('Helvetica');

    doc.end();

    return new Promise<Buffer>((resolve, reject) => {
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      doc.on('error', reject);
    });
  }
}
