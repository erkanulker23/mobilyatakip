import { Injectable } from '@nestjs/common';
import PDFDocument from 'pdfkit';
import { ServiceTicket } from '../../entities/service-ticket.entity';
import { CompanyService } from '../company/company.service';

@Injectable()
export class ServiceTicketPdfService {
  constructor(private companyService: CompanyService) {}

  async generate(ticket: ServiceTicket & { customer?: { name?: string; address?: string; phone?: string }; sale?: { saleNumber?: string } }): Promise<Buffer> {
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

    doc.fontSize(14).text(`SERVİS KAYDI (SSH) - ${ticket.ticketNumber}`, { align: 'center' });
    doc.moveDown(0.5);
    doc.fontSize(10);
    doc.text(`Tarih: ${new Date(ticket.openedAt).toLocaleDateString('tr-TR')}`);
    doc.text(`Durum: ${ticket.status}`);
    doc.moveDown(0.5);

    doc.font('Helvetica-Bold').text('Müşteri bilgileri', 50, doc.y);
    doc.font('Helvetica');
    doc.text(`Ad: ${(ticket.customer as any)?.name ?? '-'}`);
    const custAddr = (ticket.customer as any)?.address;
    if (custAddr) doc.text(`Adres: ${custAddr}`);
    const custPhone = (ticket.customer as any)?.phone;
    if (custPhone) doc.text(`Telefon: ${custPhone}`);
    doc.moveDown(0.5);

    doc.font('Helvetica-Bold').text('Satış', 50, doc.y);
    doc.font('Helvetica');
    doc.text(`Satış no: ${(ticket.sale as any)?.saleNumber ?? '-'}`);
    doc.moveDown(0.5);

    doc.font('Helvetica-Bold').text('Servis bilgileri', 50, doc.y);
    doc.font('Helvetica');
    doc.text(`Sorun türü: ${ticket.issueType}`);
    doc.text(`Garanti kapsamında: ${ticket.underWarranty ? 'Evet' : 'Hayır'}`);
    if (ticket.serviceChargeAmount != null && Number(ticket.serviceChargeAmount) > 0) {
      doc.text(`Servis ücreti: ${Number(ticket.serviceChargeAmount).toFixed(2)} TL`);
    } else {
      doc.text('Servis ücreti: Ücretsiz');
    }
    if (ticket.assignedDriverName || ticket.assignedVehiclePlate) {
      doc.text(`Atanan: ${ticket.assignedDriverName ?? '-'} ${ticket.assignedVehiclePlate ? `(${ticket.assignedVehiclePlate})` : ''}`);
      if (ticket.assignedDriverPhone) doc.text(`İletişim: ${ticket.assignedDriverPhone}`);
    }
    doc.moveDown(0.5);

    if (ticket.description) {
      doc.font('Helvetica-Bold').text('Açıklama', 50, doc.y);
      doc.font('Helvetica');
      doc.text(ticket.description, 50, doc.y, { width: 500 });
      doc.moveDown(0.5);
    }
    if (ticket.notes) {
      doc.font('Helvetica-Bold').text('Notlar', 50, doc.y);
      doc.font('Helvetica');
      doc.text(ticket.notes, 50, doc.y, { width: 500 });
    }

    doc.end();

    return new Promise<Buffer>((resolve, reject) => {
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      doc.on('error', reject);
    });
  }
}
