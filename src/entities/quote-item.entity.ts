import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Quote } from './quote.entity';
import { Product } from './product.entity';

@Entity('quote_items')
export class QuoteItem {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  quoteId: string;

  @Column({ type: 'uuid' })
  productId: string;

  @Column({ type: 'decimal', precision: 15, scale: 2 })
  unitPrice: number;

  @Column({ type: 'int', default: 1 })
  quantity: number;

  @Column({ type: 'decimal', precision: 5, scale: 2, default: 0 })
  lineDiscountPercent: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  lineDiscountAmount: number;

  @Column({ type: 'decimal', precision: 5, scale: 2, default: 18 })
  kdvRate: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  lineTotal: number;

  @ManyToOne(() => Quote, (q) => q.items, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'quoteId' })
  quote: Quote;

  @ManyToOne(() => Product, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'productId' })
  product: Product;
}
