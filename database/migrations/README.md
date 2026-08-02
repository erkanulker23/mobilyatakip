# Migration yazım kuralları (production güvenliği)

Bu projede **canlı veritabanı** kullanılır. Her deploy `php artisan migrate --force` çalıştırır.

## Yapın

- Yeni kolon/tablo eklerken `Schema::hasColumn` / `Schema::hasTable` kontrolü kullanın
- Foreign key işlemlerinde `App\Support\MigrationForeignKeys` helper'ını kullanın
- Migration'ları **yalnızca ileri** (additive) tasarlayın: `ADD`, `ALTER ... ADD`, nullable kolon
- Veri dönüşümü gerekiyorsa önce `UPDATE`, sonra şema değişikliği; tek seferlik çalışır (migrations tablosunda kayıtlı kalır)
- `down()` metodunda veri silmekten kaçının; production'da rollback nadiren kullanılır

## Yapmayın

- `migrate:fresh`, `migrate:refresh`, `db:wipe` — production'da **engellenmiştir**
- Mevcut kolonu `DROP` edip yeniden oluşturmak (veri kaybı)
- `TRUNCATE` veya koşulsuz `DELETE FROM`
- Hard-coded foreign key isimleri (`FK_...`) — sunucuda farklı olabilir
- Test verisi seed'lerini `DatabaseSeeder`'a eklemek

## Deploy sırasında çalışanlar

| Adım | Komut | Veri etkisi |
|------|--------|-------------|
| Migration | `migrate --force` | Sadece yeni şema |
| Seed | `SuperAdminSeeder` | Admin yoksa oluşturur |
| İl/ilçe | `turkey-locations:sync --if-empty` | Tablo boşsa doldurur |

## Yeni migration örneği

```php
public function up(): void
{
    if (! Schema::hasTable('sales') || Schema::hasColumn('sales', 'myField')) {
        return;
    }

    Schema::table('sales', function (Blueprint $table) {
        $table->string('myField', 100)->nullable()->after('notes');
    });
}
```
