import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { PaymentType } from '../common/enums/payment-type.enum';
import { Customer } from './customer.entity';
import { Kasa } from './kasa.entity';

@Entity('customer_payments')
export class CustomerPayment {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  customerId: string;

  /** Tahsilatın yapıldığı kasa/banka hesabı (opsiyonel; verilirse KasaHareket giriş oluşturulur) */
  @Column({ type: 'uuid', nullable: true })
  kasaId: string | null;

  @Column({ type: 'decimal', precision: 15, scale: 2 })
  amount: number;

  @Column({ type: 'date' })
  paymentDate: Date;

  @Column({ type: 'enum', enum: PaymentType, default: PaymentType.NAKIT })
  paymentType: PaymentType;

  @Column({ nullable: true })
  reference: string;

  @Column({ nullable: true })
  notes: string;

  @Column({ type: 'uuid', nullable: true })
  saleId: string;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Customer, (c) => c.payments, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'customerId' })
  customer: Customer;

  @ManyToOne(() => Kasa, { onDelete: 'SET NULL' })
  @JoinColumn({ name: 'kasaId' })
  kasa: Kasa | null;
}
