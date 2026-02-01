import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  OneToMany,
} from 'typeorm';
import { Product } from './product.entity';
import { Purchase } from './purchase.entity';
import { SupplierPayment } from './supplier-payment.entity';
import { SupplierStatement } from './supplier-statement.entity';

@Entity('suppliers')
export class Supplier {
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

  @Column({ default: true })
  isActive: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @OneToMany(() => Product, (p) => p.supplier)
  products: Product[];

  @OneToMany(() => Purchase, (p) => p.supplier)
  purchases: Purchase[];

  @OneToMany(() => SupplierPayment, (p) => p.supplier)
  payments: SupplierPayment[];

  @OneToMany(() => SupplierStatement, (s) => s.supplier)
  statements: SupplierStatement[];
}
