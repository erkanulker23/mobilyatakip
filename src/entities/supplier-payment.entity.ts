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
import { Supplier } from './supplier.entity';
import { Kasa } from './kasa.entity';

@Entity('supplier_payments')
export class SupplierPayment {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  supplierId: string;

  /** Ödemenin yapıldığı kasa/banka hesabı (opsiyonel; verilirse KasaHareket çıkış oluşturulur) */
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
  purchaseId: string;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Supplier, (s) => s.payments, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'supplierId' })
  supplier: Supplier;

  @ManyToOne(() => Kasa, { onDelete: 'SET NULL' })
  @JoinColumn({ name: 'kasaId' })
  kasa: Kasa | null;
}
