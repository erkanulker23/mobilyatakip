import { Module } from '@nestjs/common';
import { ConfigModule } from '@nestjs/config';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ThrottlerModule, ThrottlerGuard } from '@nestjs/throttler';
import { APP_GUARD } from '@nestjs/core';
import { getDatabaseConfig } from './config/database.config';
import { AppController } from './app.controller';
import { RootController } from './root.controller';
import { AuthModule } from './modules/auth/auth.module';
import { CompanyModule } from './modules/company/company.module';
import { ProductModule } from './modules/product/product.module';
import { WarehouseModule } from './modules/warehouse/warehouse.module';
import { StockModule } from './modules/stock/stock.module';
import { SupplierModule } from './modules/supplier/supplier.module';
import { CustomerModule } from './modules/customer/customer.module';
import { QuoteModule } from './modules/quote/quote.module';
import { SaleModule } from './modules/sale/sale.module';
import { PurchaseModule } from './modules/purchase/purchase.module';
import { SupplierPaymentModule } from './modules/supplier-payment/supplier-payment.module';
import { SupplierStatementModule } from './modules/supplier-statement/supplier-statement.module';
import { CustomerPaymentModule } from './modules/customer-payment/customer-payment.module';
import { PersonnelModule } from './modules/personnel/personnel.module';
import { PersonnelCategoryModule } from './modules/personnel-category/personnel-category.module';
import { KasaModule } from './modules/kasa/kasa.module';
import { KasaHareketModule } from './modules/kasa-hareket/kasa-hareket.module';
import { ExpenseModule } from './modules/expense/expense.module';
import { ExpenseCategoryModule } from './modules/expense-category/expense-category.module';
import { ServiceTicketModule } from './modules/service-ticket/service-ticket.module';
import { UploadModule } from './modules/upload/upload.module';
import { MailModule } from './modules/mail/mail.module';
import { NotificationModule } from './modules/notification/notification.module';
import { AuditLogModule } from './modules/audit-log/audit-log.module';
import { ReportsModule } from './modules/reports/reports.module';
import { PaymentModule } from './modules/payment/payment.module';
import { SmsModule } from './modules/sms/sms.module';
import { XmlFeedModule } from './modules/xml-feed/xml-feed.module';
import { SeedModule } from './seed/seed.module';

@Module({
  controllers: [AppController, RootController],
  providers: [
    { provide: APP_GUARD, useClass: ThrottlerGuard },
  ],
  imports: [
    ConfigModule.forRoot({ isGlobal: true }),
    TypeOrmModule.forRoot(getDatabaseConfig()),
    ThrottlerModule.forRoot([{ ttl: 60000, limit: 100 }]),
    SeedModule,
    AuthModule,
    CompanyModule,
    ProductModule,
    WarehouseModule,
    StockModule,
    SupplierModule,
    CustomerModule,
    QuoteModule,
    SaleModule,
    PurchaseModule,
    SupplierPaymentModule,
    SupplierStatementModule,
    CustomerPaymentModule,
    PersonnelModule,
    PersonnelCategoryModule,
    KasaModule,
    KasaHareketModule,
    ExpenseModule,
    ExpenseCategoryModule,
    ServiceTicketModule,
    UploadModule,
    MailModule,
    NotificationModule,
    AuditLogModule,
    ReportsModule,
    PaymentModule,
    SmsModule,
    XmlFeedModule,
  ],
})
export class AppModule {}
