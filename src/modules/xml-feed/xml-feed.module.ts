import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { XmlFeed } from '../../entities/xml-feed.entity';
import { XmlFeedService } from './xml-feed.service';
import { XmlFeedController } from './xml-feed.controller';
import { ProductModule } from '../product/product.module';

@Module({
  imports: [TypeOrmModule.forFeature([XmlFeed]), ProductModule],
  providers: [XmlFeedService],
  controllers: [XmlFeedController],
  exports: [XmlFeedService],
})
export class XmlFeedModule {}
