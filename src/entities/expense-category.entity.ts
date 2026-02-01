import { Entity, PrimaryGeneratedColumn, Column } from 'typeorm';

@Entity('expense_categories')
export class ExpenseCategory {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ type: 'int', default: 0 })
  sortOrder: number;
}
