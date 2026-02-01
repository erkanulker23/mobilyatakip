import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as XLSX from 'xlsx';
import { XMLParser } from 'fast-xml-parser';
import { Product } from '../../entities/product.entity';
import { Supplier } from '../../entities/supplier.entity';
import { PaginatedResult, PaginationParams, normalizePagination } from '../../common/interfaces/pagination.interface';

export interface ImportRow {
  name: string;
  sku?: string;
  unitPrice?: number;
  kdvRate?: number;
  kdvIncluded?: boolean;
  description?: string;
  minStockLevel?: number;
}

@Injectable()
export class ProductService {
  constructor(
    @InjectRepository(Product)
    private repo: Repository<Product>,
    @InjectRepository(Supplier)
    private supplierRepo: Repository<Supplier>,
  ) {}

  /** Stok kodu girilmezse otomatik üretir: PRD-YYYY-NNNNN */
  private async generateSku(): Promise<string> {
    const year = new Date().getFullYear();
    const prefix = `PRD-${year}-`;
    const last = await this.repo
      .createQueryBuilder('p')
      .where('p.sku LIKE :prefix', { prefix: `${prefix}%` })
      .orderBy('p.sku', 'DESC')
      .getOne();
    const nextNum = last && last.sku
      ? parseInt(last.sku.replace(prefix, ''), 10) + 1
      : 1;
    return `${prefix}${String(nextNum).padStart(5, '0')}`;
  }

  async create(data: Partial<Product>): Promise<Product> {
    const sku = (data.sku && String(data.sku).trim()) || await this.generateSku();
    const product = this.repo.create({ ...data, sku });
    return this.repo.save(product);
  }

  async importFromExcel(buffer: Buffer, supplierId?: string): Promise<{ created: number; errors: string[] }> {
    const workbook = XLSX.read(buffer, { type: 'buffer' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const rows = XLSX.utils.sheet_to_json(sheet) as Record<string, unknown>[];
    return this.importRows(rows, supplierId);
  }

  async importFromXml(buffer: Buffer, supplierId?: string): Promise<{ created: number; errors: string[] }> {
    const parser = new XMLParser({ ignoreAttributes: false });
    const parsed = parser.parse(buffer.toString('utf-8'));
    const root = parsed?.products ?? parsed?.Products ?? parsed?.root;
    if (!root) throw new Error('Geçersiz XML: products/Products/root kökü bulunamadı');
    const items = Array.isArray(root.product) ? root.product : Array.isArray(root.Product) ? root.Product : root.product ? [root.product] : root.Product ? [root.Product] : [];
    const rows = items.map((p: Record<string, unknown>) => ({
      name: p.name ?? p.Name ?? '',
      sku: p.sku ?? p.SKU ?? p.Sku,
      unitPrice: Number(p.unitPrice ?? p.UnitPrice ?? p.fiyat ?? 0),
      kdvRate: Number(p.kdvRate ?? p.KDV ?? 18),
      kdvIncluded: p.kdvIncluded === true || p.kdvIncluded === 'true' || p.KDVDahil === 'true',
      description: p.description ?? p.Description ?? '',
      minStockLevel: Number(p.minStockLevel ?? p.min_stock ?? 0) || 0,
    }));
    return this.importRows(rows, supplierId);
  }

  private async importRows(rows: Record<string, unknown>[], supplierId?: string): Promise<{ created: number; errors: string[] }> {
    const errors: string[] = [];
    let created = 0;
    for (let i = 0; i < rows.length; i++) {
      const r = rows[i];
      const name = String(r?.name ?? r?.Name ?? '').trim();
      if (!name) {
        errors.push(`Satır ${i + 1}: Ürün adı boş`);
        continue;
      }
      try {
        const skuRaw = (r?.sku ?? r?.SKU ?? '') ? String(r.sku ?? r.SKU).trim() : '';
        const sku = skuRaw || (await this.generateSku());
        const product = this.repo.create({
          name,
          sku,
          unitPrice: Number(r?.unitPrice ?? r?.UnitPrice ?? r?.fiyat ?? 0) || 0,
          kdvRate: Number(r?.kdvRate ?? r?.KDV ?? 18) || 18,
          kdvIncluded: r?.kdvIncluded === true || r?.kdvIncluded === 'true' || r?.KDVDahil === 'true',
          description: (r?.description ?? r?.Description) ? String(r.description ?? r.Description).trim() : undefined,
          minStockLevel: Number(r?.minStockLevel ?? r?.min_stock ?? 0) || 0,
          supplierId: supplierId || undefined,
        });
        await this.repo.save(product);
        created++;
      } catch (e) {
        errors.push(`Satır ${i + 1}: ${e instanceof Error ? e.message : 'Bilinmeyen hata'}`);
      }
    }
    return { created, errors };
  }

  async findAll(params?: {
    search?: string;
    supplierId?: string;
    active?: boolean;
    page?: number;
    limit?: number;
  }): Promise<PaginatedResult<Product>> {
    const { page, limit, skip } = normalizePagination({ page: params?.page, limit: params?.limit });
    const qb = this.repo
      .createQueryBuilder('p')
      .leftJoinAndSelect('p.supplier', 's')
      .orderBy('p.name', 'ASC');
    if (params?.search) {
      qb.andWhere('(p.name LIKE :search OR p.sku LIKE :search)', { search: `%${params.search}%` });
    }
    if (params?.supplierId) qb.andWhere('p.supplierId = :supplierId', { supplierId: params.supplierId });
    if (params?.active !== undefined) qb.andWhere('p.isActive = :active', { active: params.active });
    const total = await qb.getCount();
    const data = await qb.skip(skip).take(limit).getMany();
    return {
      data,
      total,
      page,
      limit,
      totalPages: Math.ceil(total / limit) || 1,
    };
  }

  async findOne(id: string): Promise<Product> {
    const p = await this.repo.findOne({ where: { id }, relations: ['supplier'] });
    if (!p) throw new Error('Ürün bulunamadı');
    return p;
  }

  async update(id: string, data: Partial<Product>): Promise<Product> {
    await this.repo.update(id, data as any);
    return this.findOne(id);
  }

  async remove(id: string): Promise<void> {
    await this.repo.delete(id);
  }

  async bulkDelete(ids: string[]): Promise<{ deleted: number }> {
    if (!ids?.length) return { deleted: 0 };
    const result = await this.repo.delete(ids);
    return { deleted: result.affected ?? 0 };
  }

  /** Belirtilen external source (feed URL) ile kayıtlı tüm ürünleri siler. */
  async deleteByExternalSource(externalSource: string): Promise<{ deleted: number }> {
    if (!externalSource?.trim()) return { deleted: 0 };
    const result = await this.repo.delete({ externalSource: externalSource.trim() });
    return { deleted: result.affected ?? 0 };
  }

  /** Excel veya XML olarak dışa aktar; includeExisting true ise mevcut ürünler de dahil edilir (filtreye göre). */
  async exportToExcel(params?: {
    includeExisting?: boolean;
    search?: string;
    supplierId?: string;
    active?: boolean;
    limit?: number;
  }): Promise<Buffer> {
    const limit = params?.limit ?? 10000;
    const qb = this.repo
      .createQueryBuilder('p')
      .leftJoinAndSelect('p.supplier', 's')
      .orderBy('p.name', 'ASC');
    if (params?.includeExisting !== false) {
      if (params?.search) qb.andWhere('(p.name LIKE :search OR p.sku LIKE :search)', { search: `%${params.search}%` });
      if (params?.supplierId) qb.andWhere('p.supplierId = :supplierId', { supplierId: params.supplierId });
      if (params?.active !== undefined) qb.andWhere('p.isActive = :active', { active: params.active });
    }
    const products = await qb.take(limit).getMany();
    const rows = products.map((p) => ({
      name: p.name,
      sku: p.sku ?? '',
      unitPrice: Number(p.unitPrice ?? 0),
      kdvRate: Number(p.kdvRate ?? 18),
      kdvIncluded: p.kdvIncluded !== false,
      description: p.description ?? '',
      minStockLevel: p.minStockLevel ?? 0,
      supplierName: (p as any).supplier?.name ?? '',
    }));
    const worksheet = XLSX.utils.json_to_sheet(rows);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Ürünler');
    return Buffer.from(XLSX.write(workbook, { type: 'buffer', bookType: 'xlsx' }));
  }

  /**
   * RSS/Google Shopping XML feed'den (g: namespace) ürünleri çeker.
   * Tedarikçi verilmezse g:brand ile bulunur veya yeni tedarikçi oluşturulur.
   * Resimler (image_link + additional_image_link) dahil edilir.
   */
  async importFromFeedUrl(
    feedUrl: string,
    supplierId?: string,
  ): Promise<{ created: number; updated: number; errors: string[] }> {
    const errors: string[] = [];
    let created = 0;
    let updated = 0;

    const res = await fetch(feedUrl, { headers: { Accept: 'application/xml, text/xml, */*' } });
    if (!res.ok) throw new Error(`Feed alınamadı: ${res.status} ${res.statusText}`);
    const xmlText = await res.text();

    const parser = new XMLParser({
      ignoreAttributes: false,
      attributeNamePrefix: '@_',
      parseTagValue: false,
    });
    const parsed = parser.parse(xmlText);
    const channel = parsed?.rss?.channel ?? parsed?.channel;
    if (!channel) throw new Error('Geçersiz RSS: channel bulunamadı');

    const rawItems = channel.item;
    const items = Array.isArray(rawItems) ? rawItems : rawItems ? [rawItems] : [];
    if (items.length === 0) return { created: 0, updated: 0, errors: ['Feed içinde ürün (item) bulunamadı.'] };

    const text = (node: unknown): string => {
      if (node == null) return '';
      if (typeof node === 'string') return node.trim();
      if (typeof node === 'object' && node !== null && '#text' in node) return String((node as { '#text'?: string })['#text'] ?? '').trim();
      return String(node).trim();
    };

    const tag = (item: Record<string, unknown>, ...keys: string[]): string => {
      for (const k of keys) {
        const v = item[k];
        if (v !== undefined && v !== null) return text(v);
      }
      return '';
    };

    const numPrice = (raw: string): number => {
      const s = String(raw || '').replace(/\s*TRY\s*/gi, '').trim().replace(',', '.');
      const n = parseFloat(s);
      return Number.isFinite(n) ? n : 0;
    };

    let resolvedSupplierId = supplierId ?? null;
    if (!resolvedSupplierId) {
      const brand = tag(items[0] as Record<string, unknown>, 'g:brand', 'brand');
      const channelLink = tag(channel as Record<string, unknown>, 'link');
      const name = brand || new URL(channelLink || 'https://unknown').hostname.replace(/^www\./, '') || 'Bilinmeyen Tedarikçi';
      let supplier = await this.supplierRepo.findOne({ where: { name: name.trim() } });
      if (!supplier) {
        supplier = this.supplierRepo.create({ name: name.trim() });
        supplier = await this.supplierRepo.save(supplier);
      }
      resolvedSupplierId = supplier.id;
    }

    const externalSource = feedUrl.trim();

    for (let i = 0; i < items.length; i++) {
      const it = items[i] as Record<string, unknown>;
      const externalId = tag(it, 'g:id', 'id');
      const name = tag(it, 'g:title', 'title');
      if (!name) {
        errors.push(`Satır ${i + 1}: Başlık (g:title) boş, atlandı.`);
        continue;
      }
      const description = tag(it, 'g:description', 'description');
      const mainImage = tag(it, 'g:image_link', 'image_link');
      const addImages = it['g:additional_image_link'] ?? it['additional_image_link'];
      const additionalArr = Array.isArray(addImages) ? addImages : addImages != null ? [addImages] : [];
      const allImageUrls = [mainImage, ...additionalArr.map((x: unknown) => text(x))].filter(Boolean);

      const priceRaw = tag(it, 'g:price', 'price');
      const salePriceRaw = tag(it, 'g:sale_price', 'sale_price');
      const unitPrice = numPrice(salePriceRaw) || numPrice(priceRaw) || 0;
      const skuRaw = tag(it, 'g:mpn', 'mpn');
      const sku = skuRaw || (externalId ? `XML-${externalId}` : undefined);

      try {
        const existing = externalId
          ? await this.repo.findOne({ where: { externalId, externalSource } })
          : null;

        if (existing) {
          existing.name = name;
          existing.description = description || existing.description;
          existing.unitPrice = unitPrice;
          existing.images = allImageUrls.length ? allImageUrls : existing.images;
          existing.sku = sku || existing.sku;
          existing.supplierId = resolvedSupplierId ?? undefined;
          await this.repo.save(existing);
          updated++;
        } else {
          const newSku = sku || await this.generateSku();
          const product = this.repo.create({
            name,
            sku: newSku,
            unitPrice,
            kdvIncluded: true,
            kdvRate: 18,
            description: description || undefined,
            images: allImageUrls.length ? allImageUrls : null,
            supplierId: resolvedSupplierId ?? undefined,
            externalId: externalId || null,
            externalSource,
          });
          await this.repo.save(product);
          created++;
        }
      } catch (e) {
        errors.push(`Satır ${i + 1} (${name}): ${e instanceof Error ? e.message : 'Bilinmeyen hata'}`);
      }
    }

    return { created, updated, errors };
  }
}
