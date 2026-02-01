import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToMany,
} from 'typeorm';
import { Quote } from './quote.entity';
import { Sale } from './sale.entity';
import { CustomerPayment } from './customer-payment.entity';
import { ServiceTicket } from './service-ticket.entity';

@Entity('customers')
export class Customer {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ nullable: true })
  email: string;

  @Column({ nullable: true })
  phone: string;

  @Column({ nullable: true })
  address: string;

  @Column({ nullable: true })
  taxNumber: string;

  @Column({ nullable: true })
  taxOffice: string;

  /** TC Kimlik No (fatura için zorunlu değil) */
  @Column({ nullable: true })
  identityNumber: string;

  @Column({ default: true })
  isActive: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @OneToMany(() => Quote, (q) => q.customer)
  quotes: Quote[];

  @OneToMany(() => Sale, (s) => s.customer)
  sales: Sale[];

  @OneToMany(() => CustomerPayment, (p) => p.customer)
  payments: CustomerPayment[];

  @OneToMany(() => ServiceTicket, (t) => t.customer)
  serviceTickets: ServiceTicket[];
}
