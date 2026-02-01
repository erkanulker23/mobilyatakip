import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from 'typeorm';
import { QuoteStatus } from '../common/enums/quote-status.enum';
import { Customer } from './customer.entity';
import { Personnel } from './personnel.entity';
import { QuoteItem } from './quote-item.entity';
import { QuoteRevision } from './quote-revision.entity';
import { Sale } from './sale.entity';

@Entity('quotes')
export class Quote {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ unique: true })
  quoteNumber: string;

  @Column({ type: 'uuid' })
  customerId: string;

  @Column({ type: 'enum', enum: QuoteStatus, default: QuoteStatus.TASLAK })
  status: QuoteStatus;

  @Column({ type: 'decimal', precision: 5, scale: 2, default: 0 })
  generalDiscountPercent: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  generalDiscountAmount: number;

  @Column({ type: 'int', default: 1 })
  revision: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  subtotal: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  kdvTotal: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  grandTotal: number;

  @Column({ nullable: true })
  validUntil: Date;

  @Column({ nullable: true })
  notes: string;

  @Column({ type: 'uuid', nullable: true })
  convertedSaleId: string;

  @Column({ type: 'uuid', nullable: true })
  personnelId: string;

  @Column({ nullable: true })
  customerSource: string;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Customer, (c) => c.quotes, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'customerId' })
  customer: Customer;

  @ManyToOne(() => Personnel, (p) => p.quotes, { onDelete: 'SET NULL', nullable: true })
  @JoinColumn({ name: 'personnelId' })
  personnel: Personnel;

  @OneToMany(() => QuoteItem, (i) => i.quote, { cascade: true })
  items: QuoteItem[];

  @OneToMany(() => QuoteRevision, (r) => r.quote)
  revisions: QuoteRevision[];

  @ManyToOne(() => Sale, { nullable: true })
  @JoinColumn({ name: 'convertedSaleId' })
  convertedSale: Sale;
}
