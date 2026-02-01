import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Purchase } from './purchase.entity';
import { Product } from './product.entity';

@Entity('purchase_items')
export class PurchaseItem {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  purchaseId: string;

  @Column({ type: 'uuid' })
  productId: string;

  @Column({ type: 'decimal', precision: 15, scale: 2 })
  unitPrice: number;

  @Column({ type: 'int', default: 1 })
  quantity: number;

  @Column({ type: 'decimal', precision: 5, scale: 2, default: 18 })
  kdvRate: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  lineTotal: number;

  @ManyToOne(() => Purchase, (p) => p.items, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'purchaseId' })
  purchase: Purchase;

  @ManyToOne(() => Product, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'productId' })
  product: Product;
}
