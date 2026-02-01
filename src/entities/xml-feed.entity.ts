import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Supplier } from './supplier.entity';

@Entity('xml_feeds')
export class XmlFeed {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ type: 'varchar', length: 2048 })
  url: string;

  @Column({ type: 'uuid', nullable: true })
  supplierId: string | null;

  @CreateDateColumn()
  createdAt: Date;

  @ManyToOne(() => Supplier, { onDelete: 'SET NULL', nullable: true })
  @JoinColumn({ name: 'supplierId' })
  supplier: Supplier | null;
}
