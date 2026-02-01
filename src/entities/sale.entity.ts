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
import { Customer } from './customer.entity';
import { Quote } from './quote.entity';
import { SaleItem } from './sale-item.entity';
import { ServiceTicket } from './service-ticket.entity';

@Entity('sales')
export class Sale {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ unique: true })
  saleNumber: string;

  @Column({ type: 'uuid' })
  customerId: string;

  @Column({ type: 'uuid', nullable: true })
  quoteId: string;

  @Column({ type: 'date' })
  saleDate: Date;

  @Column({ type: 'date', nullable: true })
  dueDate: Date;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  subtotal: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  kdvTotal: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  grandTotal: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  paidAmount: number;

  @Column({ nullable: true })
  notes: string;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Customer, (c) => c.sales, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'customerId' })
  customer: Customer;

  @ManyToOne(() => Quote, (q) => q.convertedSale, { nullable: true })
  @JoinColumn({ name: 'quoteId' })
  quote: Quote;

  @OneToMany(() => SaleItem, (i) => i.sale, { cascade: true })
  items: SaleItem[];

  @OneToMany(() => ServiceTicket, (t) => t.sale)
  serviceTickets: ServiceTicket[];
}
