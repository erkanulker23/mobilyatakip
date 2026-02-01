import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
} from 'typeorm';

@Entity('audit_logs')
export class AuditLog {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid', nullable: true })
  userId: string;

  @Column()
  entity: string;

  @Column({ type: 'uuid', nullable: true })
  entityId: string;

  @Column()
  action: string;

  @Column({ type: 'json', nullable: true })
  oldValue: Record<string, unknown>;

  @Column({ type: 'json', nullable: true })
  newValue: Record<string, unknown>;

  @Column({ nullable: true })
  ipAddress: string;

  @Column({ nullable: true })
  userAgent: string;

  @CreateDateColumn()
  createdAt: Date;
}
