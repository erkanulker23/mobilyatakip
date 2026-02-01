import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
  OneToMany,
  JoinColumn,
} from 'typeorm';
import { ServiceTicket } from './service-ticket.entity';
import { User } from './user.entity';
import { ServicePart } from './service-part.entity';

@Entity('service_ticket_details')
export class ServiceTicketDetail {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  ticketId: string;

  @Column({ type: 'uuid' })
  userId: string;

  @Column({ type: 'text' })
  action: string;

  @Column({ type: 'timestamp', default: () => 'CURRENT_TIMESTAMP' })
  actionDate: Date;

  @Column({ nullable: true })
  notes: string;

  @Column({ type: 'json', nullable: true })
  images: string[];

  @CreateDateColumn()
  createdAt: Date;

  @ManyToOne(() => ServiceTicket, (t) => t.details, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'ticketId' })
  ticket: ServiceTicket;

  @ManyToOne(() => User, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'userId' })
  user: User;

  @OneToMany(() => ServicePart, (p) => p.detail, { cascade: true })
  parts: ServicePart[];
}
