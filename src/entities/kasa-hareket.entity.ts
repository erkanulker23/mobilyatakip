import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  CreateDateColumn,
  UpdateDateColumn,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { Kasa } from './kasa.entity';
import { User } from './user.entity';

/** giris = kasaya para ekleme, cikis = kasadan çıkış (ödeme/masraf), virman = hesaplar arası transfer */
export type KasaHareketType = 'giris' | 'cikis' | 'virman';

@Entity('kasa_hareket')
export class KasaHareket {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'varchar', length: 20 })
  type: KasaHareketType;

  @Column({ type: 'decimal', precision: 15, scale: 2 })
  amount: number;

  @Column({ type: 'date' })
  movementDate: Date;

  @Column({ type: 'varchar', length: 500, nullable: true })
  description: string;

  /** Giriş için: paranın eklendiği kasa */
  @Column({ type: 'uuid', nullable: true })
  kasaId: string;

  /** Virman için: çıkış yapan kasa */
  @Column({ type: 'uuid', nullable: true })
  fromKasaId: string;

  /** Virman için: giriş yapan kasa */
  @Column({ type: 'uuid', nullable: true })
  toKasaId: string;

  @Column({ type: 'uuid', nullable: true })
  createdBy: string;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @ManyToOne(() => Kasa, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'kasaId' })
  kasa: Kasa;

  @ManyToOne(() => Kasa, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'fromKasaId' })
  fromKasa: Kasa;

  @ManyToOne(() => Kasa, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'toKasaId' })
  toKasa: Kasa;

  @ManyToOne(() => User, { nullable: true, onDelete: 'SET NULL' })
  @JoinColumn({ name: 'createdBy' })
  createdByUser: User;
}
