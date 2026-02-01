import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToMany,
} from 'typeorm';
import { Quote } from './quote.entity';

@Entity('personnel')
export class Personnel {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ nullable: true })
  email: string;

  @Column({ nullable: true })
  phone: string;

  @Column({ nullable: true })
  category: string;

  /** Personel görevi (örn: Satış Temsilcisi, Şoför) */
  @Column({ nullable: true })
  title: string;

  @Column({ nullable: true })
  vehiclePlate: string;

  @Column({ type: 'text', nullable: true })
  driverInfo: string;

  @Column({ default: true })
  isActive: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @OneToMany(() => Quote, (q) => q.personnel)
  quotes: Quote[];
}
