import { jsPDF } from 'jspdf';

export interface CompanyInfo {
  name?: string;
  address?: string;
  phone?: string;
  email?: string;
  logoUrl?: string;
}

/** Firma bilgisi ve başlık ile PDF sayfası başlatır (logo URL varsa yüklenmeye çalışılır). */
export function addPdfHeader(doc: jsPDF, company: CompanyInfo | null, title: string, yStart: number = 20): number {
  let y = yStart;
  doc.setFontSize(18);
  doc.text(company?.name ?? 'Firma Adı', 14, y);
  y += 8;
  doc.setFontSize(10);
  if (company?.address) {
    doc.text(company.address, 14, y);
    y += 6;
  }
  const contact: string[] = [];
  if (company?.phone) contact.push(`Tel: ${company.phone}`);
  if (company?.email) contact.push(company.email);
  if (contact.length) {
    doc.text(contact.join(' | '), 14, y);
    y += 10;
  } else {
    y += 4;
  }
  doc.setDrawColor(200, 200, 200);
  doc.line(14, y, 196, y);
  y += 10;
  doc.setFontSize(14);
  doc.text(title, 14, y);
  y += 10;
  return y;
}

/** Teklif için jspdf ile PDF oluşturur (companies tablosundaki logo ve adres kullanılır). */
export function generateQuotePdf(
  quote: {
    quoteNumber: string;
    revision?: number;
    subtotal?: number;
    kdvTotal?: number;
    grandTotal?: number;
    notes?: string;
    customer?: { name?: string };
    items?: Array<{ product?: { name?: string }; unitPrice?: number; quantity?: number; lineTotal?: number }>;
  },
  company: CompanyInfo | null,
): jsPDF {
  const doc = new jsPDF({ unit: 'mm', format: 'a4' });
  let y = addPdfHeader(doc, company, `TEKLİF - ${quote.quoteNumber} (Rev. v${quote.revision ?? 1})`);

  doc.setFontSize(10);
  doc.text(`Müşteri: ${quote.customer?.name ?? '—'}`, 14, y);
  y += 8;

  if (quote.items?.length) {
    doc.setFontSize(9);
    doc.text('Ürün', 14, y);
    doc.text('Birim Fiyat', 80, y);
    doc.text('Adet', 120, y);
    doc.text('Satır Toplam', 150, y);
    y += 6;
    doc.line(14, y, 196, y);
    y += 6;
    for (const item of quote.items) {
      const name = (item.product?.name ?? '—').substring(0, 35);
      doc.text(name, 14, y);
      doc.text(Number(item.unitPrice ?? 0).toFixed(2), 80, y);
      doc.text(String(item.quantity ?? 0), 120, y);
      doc.text(Number(item.lineTotal ?? 0).toFixed(2), 150, y);
      y += 6;
    }
    y += 4;
  }

  doc.setFontSize(10);
  doc.text(`Ara Toplam: ${Number(quote.subtotal ?? 0).toFixed(2)} TL`, 14, y);
  y += 6;
  doc.text(`KDV Toplam: ${Number(quote.kdvTotal ?? 0).toFixed(2)} TL`, 14, y);
  y += 6;
  doc.setFont('helvetica', 'bold');
  doc.text(`GENEL TOPLAM: ${Number(quote.grandTotal ?? 0).toFixed(2)} TL`, 14, y);
  doc.setFont('helvetica', 'normal');
  y += 10;

  if (quote.notes) {
    doc.setFontSize(9);
    doc.text('Not: ' + quote.notes, 14, y);
  }

  return doc;
}

/** Satış fişi için jspdf ile PDF oluşturur. */
export function generateSalePdf(
  sale: {
    saleNumber: string;
    saleDate?: string;
    subtotal?: number;
    kdvTotal?: number;
    grandTotal?: number;
    paidAmount?: number;
    notes?: string;
    customer?: { name?: string };
    items?: Array<{ product?: { name?: string }; unitPrice?: number; quantity?: number; lineTotal?: number }>;
  },
  company: CompanyInfo | null,
): jsPDF {
  const doc = new jsPDF({ unit: 'mm', format: 'a4' });
  let y = addPdfHeader(doc, company, `SATIŞ FİŞİ - ${sale.saleNumber}`);

  doc.setFontSize(10);
  doc.text(`Müşteri: ${sale.customer?.name ?? '—'}`, 14, y);
  y += 6;
  if (sale.saleDate) {
    doc.text(`Tarih: ${new Date(sale.saleDate).toLocaleDateString('tr-TR')}`, 14, y);
    y += 8;
  } else {
    y += 4;
  }

  if (sale.items?.length) {
    doc.setFontSize(9);
    doc.text('Ürün', 14, y);
    doc.text('Birim Fiyat', 80, y);
    doc.text('Adet', 120, y);
    doc.text('Satır Toplam', 150, y);
    y += 6;
    doc.line(14, y, 196, y);
    y += 6;
    for (const item of sale.items) {
      const name = (item.product?.name ?? '—').substring(0, 35);
      doc.text(name, 14, y);
      doc.text(Number(item.unitPrice ?? 0).toFixed(2), 80, y);
      doc.text(String(item.quantity ?? 0), 120, y);
      doc.text(Number(item.lineTotal ?? 0).toFixed(2), 150, y);
      y += 6;
    }
    y += 4;
  }

  doc.setFontSize(10);
  doc.text(`Ara Toplam: ${Number(sale.subtotal ?? 0).toFixed(2)} TL`, 14, y);
  y += 6;
  doc.text(`KDV Toplam: ${Number(sale.kdvTotal ?? 0).toFixed(2)} TL`, 14, y);
  y += 6;
  doc.setFont('helvetica', 'bold');
  doc.text(`GENEL TOPLAM: ${Number(sale.grandTotal ?? 0).toFixed(2)} TL`, 14, y);
  doc.setFont('helvetica', 'normal');
  y += 6;
  doc.text(`Ödenen: ${Number(sale.paidAmount ?? 0).toFixed(2)} TL`, 14, y);
  y += 10;

  if (sale.notes) {
    doc.setFontSize(9);
    doc.text('Not: ' + sale.notes, 14, y);
  }

  return doc;
}

/** Alış faturası için jspdf ile PDF oluşturur. */
export function generatePurchasePdf(
  purchase: {
    purchaseNumber: string;
    purchaseDate?: string;
    dueDate?: string;
    subtotal?: number;
    kdvTotal?: number;
    grandTotal?: number;
    paidAmount?: number;
    notes?: string;
    supplier?: { name?: string };
    items?: Array<{ product?: { name?: string }; unitPrice?: number; quantity?: number; lineTotal?: number }>;
  },
  company: CompanyInfo | null,
): jsPDF {
  const doc = new jsPDF({ unit: 'mm', format: 'a4' });
  let y = addPdfHeader(doc, company, `ALIŞ FATURASI - ${purchase.purchaseNumber}`);

  doc.setFontSize(10);
  doc.text(`Tedarikçi: ${purchase.supplier?.name ?? '—'}`, 14, y);
  y += 6;
  if (purchase.purchaseDate) {
    doc.text(`Tarih: ${new Date(purchase.purchaseDate).toLocaleDateString('tr-TR')}`, 14, y);
    y += 6;
  }
  if (purchase.dueDate) {
    doc.text(`Vade: ${new Date(purchase.dueDate).toLocaleDateString('tr-TR')}`, 14, y);
    y += 8;
  } else {
    y += 4;
  }

  if (purchase.items?.length) {
    doc.setFontSize(9);
    doc.text('Ürün', 14, y);
    doc.text('Birim Fiyat', 80, y);
    doc.text('Adet', 120, y);
    doc.text('Satır Toplam', 150, y);
    y += 6;
    doc.line(14, y, 196, y);
    y += 6;
    for (const item of purchase.items) {
      const name = (item.product?.name ?? '—').substring(0, 35);
      doc.text(name, 14, y);
      doc.text(Number(item.unitPrice ?? 0).toFixed(2), 80, y);
      doc.text(String(item.quantity ?? 0), 120, y);
      doc.text(Number(item.lineTotal ?? 0).toFixed(2), 150, y);
      y += 6;
    }
    y += 4;
  }

  doc.setFontSize(10);
  doc.text(`Ara Toplam: ${Number(purchase.subtotal ?? 0).toFixed(2)} TL`, 14, y);
  y += 6;
  doc.text(`KDV Toplam: ${Number(purchase.kdvTotal ?? 0).toFixed(2)} TL`, 14, y);
  y += 6;
  doc.setFont('helvetica', 'bold');
  doc.text(`GENEL TOPLAM: ${Number(purchase.grandTotal ?? 0).toFixed(2)} TL`, 14, y);
  doc.setFont('helvetica', 'normal');
  y += 6;
  doc.text(`Ödenen: ${Number(purchase.paidAmount ?? 0).toFixed(2)} TL`, 14, y);
  y += 10;

  if (purchase.notes) {
    doc.setFontSize(9);
    doc.text('Not: ' + purchase.notes, 14, y);
  }

  return doc;
}
