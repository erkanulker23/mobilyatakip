import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToMany,
} from 'typeorm';
import { Expense } from './expense.entity';

@Entity('kasa')
export class Kasa {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  /** kasa | banka */
  @Column({ default: 'kasa' })
  type: string;

  @Column({ nullable: true })
  accountNumber: string;

  @Column({ nullable: true })
  iban: string;

  @Column({ nullable: true })
  bankName: string;

  @Column({ type: 'decimal', precision: 15, scale: 2, default: 0 })
  openingBalance: number;

  @Column({ default: 'TRY' })
  currency: string;

  @Column({ default: true })
  isActive: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @OneToMany(() => Expense, (e) => e.kasa)
  expenses: Expense[];
}
