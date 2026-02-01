import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { XmlFeed } from '../../entities/xml-feed.entity';
import { ProductService } from '../product/product.service';

@Injectable()
export class XmlFeedService {
  constructor(
    @InjectRepository(XmlFeed)
    private repo: Repository<XmlFeed>,
    private productService: ProductService,
  ) {}

  async findAll(): Promise<XmlFeed[]> {
    return this.repo.find({
      relations: ['supplier'],
      order: { name: 'ASC' },
    });
  }

  async create(data: { name: string; url: string; supplierId?: string | null }): Promise<XmlFeed> {
    const feed = this.repo.create(data);
    return this.repo.save(feed);
  }

  async remove(id: string, deleteProducts?: boolean): Promise<{ productsDeleted: number }> {
    const feed = await this.repo.findOne({ where: { id } });
    if (!feed) throw new NotFoundException('Feed bulunamadı');
    let productsDeleted = 0;
    if (deleteProducts && feed.url) {
      const result = await this.productService.deleteByExternalSource(feed.url);
      productsDeleted = result.deleted;
    }
    await this.repo.delete(id);
    return { productsDeleted };
  }
}
