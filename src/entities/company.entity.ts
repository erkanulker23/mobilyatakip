import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
} from 'typeorm';

@Entity('companies')
export class Company {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column()
  name: string;

  @Column({ nullable: true })
  address: string;

  @Column({ nullable: true })
  taxNumber: string;

  @Column({ nullable: true })
  taxOffice: string;

  @Column({ nullable: true })
  phone: string;

  @Column({ nullable: true })
  email: string;

  @Column({ nullable: true })
  logoUrl: string;

  @Column({ nullable: true })
  website: string;

  /** NTGSM SMS API */
  @Column({ nullable: true })
  ntgsmUsername: string;

  @Column({ nullable: true })
  ntgsmPassword: string;

  @Column({ nullable: true })
  ntgsmOriginator: string;

  @Column({ nullable: true })
  ntgsmApiUrl: string;

  /** PayTR Ödeme Sistemi */
  @Column({ nullable: true })
  paytrMerchantId: string;

  @Column({ nullable: true })
  paytrMerchantKey: string;

  @Column({ nullable: true })
  paytrMerchantSalt: string;

  @Column({ type: 'boolean', default: false })
  paytrTestMode: boolean;

  /** Mail (SMTP) Ayarları */
  @Column({ nullable: true })
  mailHost: string;

  @Column({ type: 'int', nullable: true })
  mailPort: number;

  @Column({ nullable: true })
  mailUser: string;

  @Column({ nullable: true })
  mailPassword: string;

  @Column({ nullable: true })
  mailFrom: string;

  @Column({ type: 'boolean', default: false })
  mailSecure: boolean;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;
}
