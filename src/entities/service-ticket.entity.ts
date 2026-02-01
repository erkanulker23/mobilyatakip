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
import { ServiceTicketStatus } from '../common/enums/service-ticket-status.enum';
import { Sale } from './sale.entity';
import { Customer } from './customer.entity';
import { User } from './user.entity';
import { ServiceTicketDetail } from './service-ticket-detail.entity';

@Entity('service_tickets')
export class ServiceTicket {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ unique: true })
  ticketNumber: string;

  @Column({ type: 'uuid' })
  saleId: string;

  @Column({ type: 'uuid' })
  customerId: string;

  @Column({ type: 'enum', enum: ServiceTicketStatus, default: ServiceTicketStatus.ACILDI })
  status: ServiceTicketStatus;

  @Column({ default: false })
  underWarranty: boolean;

  @Column()
  issueType: string;

  @Column({ type: 'text', nullable: true })
  description: string;

  @Column({ type: 'uuid', nullable: true })
  assignedUserId: string;

  @Column({ nullable: true })
  assignedVehiclePlate: string;

  @Column({ nullable: true })
  assignedDriverName: string;

  @Column({ nullable: true })
  assignedDriverPhone: string;

  @Column({ type: 'timestamp', default: () => 'CURRENT_TIMESTAMP' })
  openedAt: Date;

  @Column({ type: 'timestamp', nullable: true })
  closedAt: Date;

  @Column({ nullable: true })
  notes: string;

  /** Servis ücreti (0 veya null = ücretsiz) */
  @Column({ type: 'decimal', precision: 15, scale: 2, nullable: true, default: null })
  serviceChargeAmount: number | null;

  @Column({ type: 'json', nullable: true })
  images: string[];

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Sale, (s) => s.serviceTickets, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'saleId' })
  sale: Sale;

  @ManyToOne(() => Customer, (c) => c.serviceTickets, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'customerId' })
  customer: Customer;

  @ManyToOne(() => User, { nullable: true })
  @JoinColumn({ name: 'assignedUserId' })
  assignedUser: User;

  @OneToMany(() => ServiceTicketDetail, (d) => d.ticket, { cascade: true })
  details: ServiceTicketDetail[];
}
