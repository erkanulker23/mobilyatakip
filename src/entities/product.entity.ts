import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Supplier } from './supplier.entity';

@Entity('products')
export class Product {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ nullable: true, unique: true })
  sku: string;

  /** XML/feed kaynağından gelen dış ID (örn. g:id); tekrar çekmede güncelleme için */
  @Column({ type: 'varchar', length: 255, nullable: true })
  externalId: string | null;

  /** Dış kaynak tanımı (örn. feed hostname: rossohome.com) */
  @Column({ type: 'varchar', length: 255, nullable: true })
  externalSource: string | null;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  unitPrice: number;

  /** true = fiyat KDV dahil, false = KDV hariç */
  @Column({ type: 'boolean', default: true })
  kdvIncluded: boolean;

  @Column({ type: 'decimal', precision: 5, scale: 2, default: 18 })
  kdvRate: number;

  /** Ürün resim URL'leri (JSON array) */
  @Column({ type: 'json', nullable: true })
  images: string[] | null;

  @Column({ type: 'uuid', nullable: true })
  supplierId: string;

  @Column({ type: 'int', default: 0 })
  minStockLevel: number;

  @Column({ default: true })
  isActive: boolean;

  @Column({ nullable: true })
  description: string;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Supplier, (s) => s.products, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'supplierId' })
  supplier: Supplier;
}
