import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
} from 'typeorm';
import { MailStatus } from '../common/enums/mail-status.enum';

@Entity('mail_logs')
export class MailLog {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  to: string;

  @Column({ nullable: true })
  cc: string;

  @Column()
  subject: string;

  @Column({ type: 'text', nullable: true })
  body: string;

  @Column({ type: 'enum', enum: MailStatus, default: MailStatus.GONDERILDI })
  status: MailStatus;

  @Column({ nullable: true })
  entityType: string;

  @Column({ type: 'uuid', nullable: true })
  entityId: string;

  @Column({ type: 'uuid', nullable: true })
  userId: string;

  @CreateDateColumn()
  sentAt: Date;

  @Column({ type: 'timestamp', nullable: true })
  readAt: Date;

  @Column({ nullable: true })
  errorMessage: string;
}
