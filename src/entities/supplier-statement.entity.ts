import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { StatementStatus } from '../common/enums/statement-status.enum';
import { Supplier } from './supplier.entity';

@Entity('supplier_statements')
export class SupplierStatement {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  supplierId: string;

  @Column({ type: 'date' })
  startDate: Date;

  @Column({ type: 'date' })
  endDate: Date;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  openingBalance: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  totalPurchases: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  totalPayments: number;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  closingBalance: number;

  @Column({ type: 'enum', enum: StatementStatus, default: StatementStatus.BEKLEMEDE })
  status: StatementStatus;

  @Column({ nullable: true })
  pdfUrl: string;

  @Column({ nullable: true })
  sentAt: Date;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Supplier, (s) => s.statements, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'supplierId' })
  supplier: Supplier;
}
