import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Quote } from './quote.entity';

@Entity('quote_revisions')
export class QuoteRevision {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  quoteId: string;

  @Column({ type: 'int' })
  version: number;

  @Column({ type: 'decimal', precision: 15, scale: 2 })
  grandTotal: number;

  @CreateDateColumn()
  createdAt: Date;

  @ManyToOne(() => Quote, (q) => q.revisions, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'quoteId' })
  quote: Quote;
}
