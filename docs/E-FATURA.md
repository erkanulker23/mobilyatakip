# E-Fatura Entegrasyonu

Sistem, **UBL-TR 1.2** formatında e-fatura üretir ve isteğe bağlı olarak GİB entegratörü veya özel entegratör (Fitbulut, Logo vb.) üzerinden gönderim yapabilir.

## Özellikler

- **Satış faturaları**: Satış detay sayfasından "E-Fatura XML İndir" ile UBL-TR XML indirilir, "E-Fatura Gönder" ile entegratöre gönderilir.
- **Alış faturaları**: Alış detay sayfasından aynı işlemler (XML indir / E-Fatura gönder).
- **Durum takibi**: Gönderim sonrası durum (gönderildi, kabul, red) satış/alış sayfasında gösterilir.

## Ayarlar

**Ayarlar > E-Fatura Entegrasyonu** bölümünden:

| Alan | Açıklama |
|------|----------|
| Sağlayıcı | Opsiyonel; gib, fitbulut, logo vb. |
| API Endpoint URL | Fatura gönderim API adresi (entegratöre göre değişir) |
| Kullanıcı Adı / Şifre | Entegratör veya GİB kullanıcı bilgileri |
| Test modu | Test ortamı kullanılsın mı |

Endpoint boş bırakılırsa sadece **XML indir** kullanılabilir; gönderim yapılmaz.

## Entegratör Entegrasyonu

Gönderim, `EInvoiceService::sendToProvider()` içinde **HTTP POST** ile XML gövdesi ve Basic Auth kullanır. Entegratörünüz SOAP veya farklı bir format istiyorsa:

1. `app/Services/EInvoiceService.php` içinde `sendToProvider()` metodunu kendi API’nize göre düzenleyebilirsiniz.
2. Veya `efaturaProvider` değerine göre farklı driver’lar (Fitbulut, Logo, GIB SOAP) ekleyebilirsiniz.

## Veritabanı

- **sales**: `efaturaUuid`, `efaturaStatus`, `efaturaSentAt`, `efaturaEnvelopeId`, `efaturaResponse`
- **purchases**: Aynı alanlar.
- **companies**: `efaturaProvider`, `efaturaEndpoint`, `efaturaUsername`, `efaturaPassword`, `efaturaTestMode`

## GİB Dokümantasyonu

- e-Fatura / UBL-TR kılavuzları: https://ebelge.gib.gov.tr/dosyalar/kilavuzlar/
- e-Fatura Paketi, UBL-TR 1.2.1 Paketi ve kılavuzlar bu adresten indirilebilir.
