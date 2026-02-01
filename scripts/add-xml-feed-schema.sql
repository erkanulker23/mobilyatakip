-- XML Feed ve Product alanları için şema güncellemesi
-- 502 hatası alıyorsanız bu SQL'i veritabanında çalıştırın (MySQL/MariaDB).
-- "Duplicate column name" veya "Table already exists" alırsanız o satırı atlayın.

-- 1) products tablosuna yeni kolonlar
ALTER TABLE products ADD COLUMN externalId VARCHAR(255) NULL;
ALTER TABLE products ADD COLUMN externalSource VARCHAR(255) NULL;

-- 2) xml_feeds tablosu
CREATE TABLE IF NOT EXISTS xml_feeds (
  id CHAR(36) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  url VARCHAR(2048) NOT NULL,
  supplierId CHAR(36) NULL,
  createdAt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  CONSTRAINT fk_xml_feeds_supplier FOREIGN KEY (supplierId) REFERENCES suppliers(id) ON DELETE SET NULL
);
