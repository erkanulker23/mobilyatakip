import { Injectable, OnModuleInit } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import * as bcrypt from 'bcrypt';
import { Company } from '../entities/company.entity';
import { Customer } from '../entities/customer.entity';
import { Supplier } from '../entities/supplier.entity';
import { Warehouse } from '../entities/warehouse.entity';
import { Personnel } from '../entities/personnel.entity';
import { Product } from '../entities/product.entity';
import { Kasa } from '../entities/kasa.entity';
import { Expense } from '../entities/expense.entity';
import { ExpenseCategory } from '../entities/expense-category.entity';
import { User } from '../entities/user.entity';
import { UserRole } from '../common/enums/role.enum';
import { PaymentType } from '../common/enums/payment-type.enum';
import { PurchaseService } from '../modules/purchase/purchase.service';
import { SaleService } from '../modules/sale/sale.service';
import { QuoteService } from '../modules/quote/quote.service';
import { ServiceTicketService } from '../modules/service-ticket/service-ticket.service';
import { SupplierPaymentService } from '../modules/supplier-payment/supplier-payment.service';

@Injectable()
export class DemoSeedService implements OnModuleInit {
  constructor(
    @InjectRepository(Customer)
    private customerRepo: Repository<Customer>,
    @InjectRepository(Company)
    private companyRepo: Repository<Company>,
    @InjectRepository(Supplier)
    private supplierRepo: Repository<Supplier>,
    @InjectRepository(Warehouse)
    private warehouseRepo: Repository<Warehouse>,
    @InjectRepository(Personnel)
    private personnelRepo: Repository<Personnel>,
    @InjectRepository(Product)
    private productRepo: Repository<Product>,
    @InjectRepository(Kasa)
    private kasaRepo: Repository<Kasa>,
    @InjectRepository(Expense)
    private expenseRepo: Repository<Expense>,
    @InjectRepository(ExpenseCategory)
    private categoryRepo: Repository<ExpenseCategory>,
    @InjectRepository(User)
    private userRepo: Repository<User>,
    private purchaseService: PurchaseService,
    private saleService: SaleService,
    private quoteService: QuoteService,
    private serviceTicketService: ServiceTicketService,
    private supplierPaymentService: SupplierPaymentService,
  ) {}

  async onModuleInit() {
    const hasCustomers = await this.customerRepo.count() > 0;
    if (hasCustomers) {
      console.log('[Seed] Demo veri zaten mevcut, atlanıyor.');
      return;
    }
    await this.runDemoSeed();
  }

  /** Tarayıcıdan veya curl ile GET /seed/demo çağrılarak örnek veri yüklenebilir (müşteri yoksa). */
  async runDemoSeed(): Promise<{ ok: boolean; message: string }> {
    const hasCustomers = await this.customerRepo.count() > 0;
    if (hasCustomers) {
      return { ok: false, message: 'Demo veri zaten mevcut. Veritabanında müşteri kaydı var.' };
    }

    console.log('[Seed] Örnek veriler ekleniyor...');

    let company = await this.companyRepo.findOne({ where: {} });
    if (!company) {
      company = this.companyRepo.create({
        name: 'Mobilya Takip Örnek Firma',
        address: 'Örnek Mah. Demo Cad. No:1',
        taxNumber: '1234567890',
        taxOffice: 'Örnek Vergi Dairesi',
        phone: '0212 555 00 00',
        email: 'info@mobilyatakip.local',
      });
      await this.companyRepo.save(company);
    }

    const customers = await Promise.all([
      this.customerRepo.save(this.customerRepo.create({ name: 'Ahmet Yılmaz', phone: '0532 111 22 33', email: 'ahmet@example.com', address: 'Kadıköy, İstanbul', isActive: true })),
      this.customerRepo.save(this.customerRepo.create({ name: 'Ayşe Demir', phone: '0533 222 33 44', email: 'ayse@example.com', address: 'Çankaya, Ankara', isActive: true })),
      this.customerRepo.save(this.customerRepo.create({ name: 'Mehmet Kaya', phone: '0534 333 44 55', email: 'mehmet@example.com', isActive: true })),
      this.customerRepo.save(this.customerRepo.create({ name: 'Fatma Şahin', phone: '0535 444 55 66', email: 'fatma@example.com', address: 'Bornova, İzmir', isActive: true })),
      this.customerRepo.save(this.customerRepo.create({ name: 'Ali Özkan', phone: '0536 555 66 77', email: 'ali@example.com', isActive: true })),
    ]);

    const suppliers = await Promise.all([
      this.supplierRepo.save(this.supplierRepo.create({ name: 'Mobilya Tedarik A.Ş.', phone: '0212 111 00 00', email: 'tedarik@mobilya.com', isActive: true })),
      this.supplierRepo.save(this.supplierRepo.create({ name: 'Ahşap Malzeme Ltd.', phone: '0312 222 00 00', isActive: true })),
      this.supplierRepo.save(this.supplierRepo.create({ name: 'Döşemelik Kumaş San.', phone: '0232 333 00 00', isActive: true })),
    ]);

    const warehouses = await Promise.all([
      this.warehouseRepo.save(this.warehouseRepo.create({ name: 'Ana Depo', code: 'DEPO-01', isActive: true })),
      this.warehouseRepo.save(this.warehouseRepo.create({ name: 'Şube Depo', code: 'DEPO-02', isActive: true })),
    ]);

    const personnel = await Promise.all([
      this.personnelRepo.save(this.personnelRepo.create({ name: 'Elif Satış', email: 'elif@firma.com', phone: '0537 111 11 11', category: 'satis', isActive: true })),
      this.personnelRepo.save(this.personnelRepo.create({ name: 'Can Muhasebe', email: 'can@firma.com', category: 'muhasebe', isActive: true })),
      this.personnelRepo.save(this.personnelRepo.create({ name: 'Burak Depo', email: 'burak@firma.com', category: 'depo', isActive: true })),
    ]);

    const kasas = await Promise.all([
      this.kasaRepo.save(this.kasaRepo.create({ name: 'Kasa - Ana', type: 'kasa', openingBalance: 10000, currency: 'TRY', isActive: true })),
      this.kasaRepo.save(this.kasaRepo.create({ name: 'Banka - İş Hesabı', type: 'banka', bankName: 'Ziraat Bankası', accountNumber: '12345678', openingBalance: 50000, currency: 'TRY', isActive: true })),
    ]);

    const categories = await this.categoryRepo.find({ take: 3 });
    const categoryName = categories[0]?.name ?? 'Diğer';

    const products = await Promise.all([
      this.productRepo.save(this.productRepo.create({ name: 'Yemek Masası', sku: 'YM-001', unitPrice: 3500, kdvRate: 18, supplierId: suppliers[0].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'Kanepe 3 Kişilik', sku: 'KAN-001', unitPrice: 8500, kdvRate: 18, supplierId: suppliers[0].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'Kitaplık', sku: 'KIT-001', unitPrice: 2200, kdvRate: 18, supplierId: suppliers[0].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'Çalışma Koltuğu', sku: 'CK-001', unitPrice: 1800, kdvRate: 18, supplierId: suppliers[1].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'TV Ünitesi', sku: 'TVU-001', unitPrice: 2900, kdvRate: 18, supplierId: suppliers[1].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'Yatak Çift Kişilik', sku: 'YC-001', unitPrice: 4500, kdvRate: 18, supplierId: suppliers[0].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'Yemek Sandalyesi', sku: 'YS-001', unitPrice: 450, kdvRate: 18, supplierId: suppliers[1].id, isActive: true })),
      this.productRepo.save(this.productRepo.create({ name: 'Köşe Kanepe', sku: 'KK-001', unitPrice: 12000, kdvRate: 18, supplierId: suppliers[0].id, isActive: true })),
    ]);

    const purchaseDate = new Date();
    purchaseDate.setDate(purchaseDate.getDate() - 10);
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 20);

    const firstPurchase = await this.purchaseService.create(
      {
        supplierId: suppliers[0].id,
        purchaseDate: purchaseDate.toISOString().slice(0, 10),
        dueDate: dueDate.toISOString().slice(0, 10),
        notes: 'Örnek alış faturası',
        items: [
          { productId: products[0].id, unitPrice: 2800, quantity: 5, kdvRate: 18 },
          { productId: products[1].id, unitPrice: 6500, quantity: 2, kdvRate: 18 },
          { productId: products[2].id, unitPrice: 1800, quantity: 3, kdvRate: 18 },
        ],
      },
      warehouses[0].id,
    );

    await this.supplierPaymentService.create({
      supplierId: suppliers[0].id,
      amount: 5000,
      paymentDate: new Date().toISOString().slice(0, 10),
      paymentType: PaymentType.NAKIT,
      notes: 'Örnek tedarikçi ödemesi (kasa çıkışı)',
      purchaseId: firstPurchase.id,
    });

    const sale = await this.saleService.create({
      customerId: customers[0].id,
      warehouseId: warehouses[0].id,
      dueDate: dueDate.toISOString().slice(0, 10),
      notes: 'Örnek satış',
      items: [
        { productId: products[0].id, quantity: 1, unitPrice: 3500, kdvRate: 18 },
        { productId: products[1].id, quantity: 1, unitPrice: 8500, kdvRate: 18 },
      ],
    });

    await this.quoteService.create({
      customerId: customers[1].id,
      personnelId: personnel[0].id,
      customerSource: 'mağaza',
      items: [
        { productId: products[2].id, unitPrice: 2200, quantity: 2, kdvRate: 18 },
        { productId: products[4].id, unitPrice: 2900, quantity: 1, kdvRate: 18 },
      ],
      validUntil: dueDate.toISOString().slice(0, 10),
      notes: 'Örnek teklif',
    });

    await this.serviceTicketService.create({
      saleId: sale.id,
      customerId: sale.customerId,
      underWarranty: true,
      issueType: 'Garanti',
      description: 'Örnek servis kaydı – montaj sonrası kontrol',
    });

    const demoUserEmail = 'demo@mobilyatakip.local';
    const existingDemo = await this.userRepo.findOne({ where: { email: demoUserEmail } });
    if (!existingDemo) {
      const demoHash = await bcrypt.hash('password', 10);
      await this.userRepo.save(
        this.userRepo.create({
          email: demoUserEmail,
          passwordHash: demoHash,
          name: 'Demo Kullanıcı',
          role: UserRole.SATIS,
          isActive: true,
        }),
      );
      console.log('[Seed] Demo kullanıcı eklendi: demo@mobilyatakip.local');
    }

    const expenseDate = new Date().toISOString().slice(0, 10);
    await this.expenseRepo.save(
      this.expenseRepo.create({
        amount: 500,
        expenseDate: new Date(expenseDate),
        description: 'Örnek kira ödemesi',
        category: categoryName,
        kasaId: kasas[0].id,
      }),
    );
    await this.expenseRepo.save(
      this.expenseRepo.create({
        amount: 150,
        expenseDate: new Date(expenseDate),
        description: 'Örnek elektrik faturası',
        category: 'Elektrik',
        kasaId: kasas[0].id,
      }),
    );

    console.log('[Seed] Örnek veriler eklendi: firma, müşteri, tedarikçi, depo, personel, ürün, kasa, alış, satış, teklif, SSH, masraf, demo kullanıcı.');
    return { ok: true, message: 'Örnek veriler eklendi. Sayfayı yenileyin.' };
  }
}
