# 502 Bad Gateway – XML Feed şeması

Bu hata, XML feed özelliği eklendikten sonra **veritabanında yeni tablo/kolonlar olmadığı** için backend’in çökmesinden kaynaklanabilir.

## Çözüm

1. **SQL’i çalıştırın**  
   `scripts/add-xml-feed-schema.sql` dosyasındaki komutları veritabanınızda çalıştırın.

   Örnek (MySQL CLI):
   ```bash
   mysql -u KULLANICI -p VERITABANI_ADI < scripts/add-xml-feed-schema.sql
   ```

   Veya phpMyAdmin / MySQL Workbench ile dosyayı açıp çalıştırın.

2. **"Duplicate column name"** alırsanız  
   `externalId` / `externalSource` zaten varsa o `ALTER TABLE` satırlarını atlayın.

3. **Backend’i yeniden başlatın**  
   Node/NestJS uygulamasını yeniden başlatın (PM2, systemd veya kullandığınız yöntem).

4. **Geliştirme ortamında**  
   `NODE_ENV=development` ile çalıştırıyorsanız TypeORM `synchronize: true` ile tabloları kendisi oluşturur. Yine de 502 alıyorsanız yukarıdaki SQL’i elle çalıştırıp backend’i yeniden başlatın.
