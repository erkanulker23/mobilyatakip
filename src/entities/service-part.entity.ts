import {
  Entity,
  PrimaryGeneratedColumn,
  Column,
  ManyToOne,
  JoinColumn,
} from 'typeorm';
import { ServiceTicketDetail } from './service-ticket-detail.entity';
import { Product } from './product.entity';

@Entity('service_parts')
export class ServicePart {
  @PrimaryGeneratedColumn('uuid')
  id: string;

  @Column({ type: 'uuid' })
  detailId: string;

  @Column({ type: 'uuid' })
  productId: string;

  @Column({ type: 'int', default: 1 })
  quantity: number;

  @ManyToOne(() => ServiceTicketDetail, (d) => d.parts, { onDelete: 'CASCADE' })
  @JoinColumn({ name: 'detailId' })
  detail: ServiceTicketDetail;

  @ManyToOne(() => Product, { onDelete: 'RESTRICT' })
  @JoinColumn({ name: 'productId' })
  product: Product;
}
