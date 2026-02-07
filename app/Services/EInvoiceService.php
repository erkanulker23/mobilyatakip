<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EInvoiceService
{
    private const UBL_INVOICE_NS = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    private const CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const CAC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    public function __construct(
        private ?Company $company = null
    ) {
        $this->company = $this->company ?? Company::first();
    }

    /**
     * Satış faturası için UBL-TR 1.2 uyumlu Invoice XML üretir (e-fatura / e-arşiv).
     */
    public function buildSaleInvoiceXml(Sale $sale): string
    {
        $sale->load(['customer', 'items.product']);
        $company = $this->company;
        $customer = $sale->customer;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElementNS(self::UBL_INVOICE_NS, 'Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::CBC_NS);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::CAC_NS);
        $doc->appendChild($root);

        $this->append($doc, $root, 'cbc:CustomizationID', 'TR1.2');
        $this->append($doc, $root, 'cbc:ProfileID', 'TICARIFATURA'); // e-fatura; TEMELFATURA = e-arşiv
        $this->append($doc, $root, 'cbc:ID', $sale->saleNumber);
        $this->append($doc, $root, 'cbc:IssueDate', $sale->saleDate->format('Y-m-d'));
        $this->append($doc, $root, 'cbc:IssueTime', $sale->saleDate->format('H:i:s'));
        $this->append($doc, $root, 'cbc:DocumentTypeCode', 'SATIS');
        $this->append($doc, $root, 'cbc:DocumentCurrencyCode', 'TRY');

        // Satıcı (biz)
        $supplierParty = $this->createParty($doc, $company->name, $company->address, $company->taxNumber, $company->taxOffice, $company->phone, $company->email, true);
        $accountSupplier = $doc->createElementNS(self::CAC_NS, 'cac:AccountingSupplierParty');
        $accountSupplier->appendChild($supplierParty);
        $root->appendChild($accountSupplier);

        // Alıcı (müşteri)
        $customerParty = $this->createParty(
            $doc,
            $customer?->name ?? 'Müşteri',
            $customer?->address,
            $customer?->taxNumber ?? $customer?->identityNumber,
            $customer?->taxOffice,
            $customer?->phone,
            $customer?->email,
            false
        );
        $accountCustomer = $doc->createElementNS(self::CAC_NS, 'cac:AccountingCustomerParty');
        $accountCustomer->appendChild($customerParty);
        $root->appendChild($accountCustomer);

        // Kalemler (LineExtensionAmount = matrah, satır vergisi eklenir)
        foreach ($sale->items as $index => $item) {
            $line = $this->createInvoiceLine($doc, $index + 1, $item->product?->name ?? 'Ürün', $item->quantity, $item->unitPrice, $item->kdvRate ?? 18, $item->lineTotal, $sale->kdvIncluded ?? true);
            $root->appendChild($line);
        }

        // KDV toplam
        $taxTotal = $doc->createElementNS(self::CAC_NS, 'cac:TaxTotal');
        $this->append($doc, $taxTotal, 'cbc:TaxAmount', $this->amount($sale->kdvTotal), ['currencyID' => 'TRY']);
        $root->appendChild($taxTotal);

        // Toplamlar
        $monetary = $doc->createElementNS(self::CAC_NS, 'cac:LegalMonetaryTotal');
        $this->append($doc, $monetary, 'cbc:LineExtensionAmount', $this->amount($sale->subtotal), ['currencyID' => 'TRY']);
        $this->append($doc, $monetary, 'cbc:TaxExclusiveAmount', $this->amount($sale->subtotal), ['currencyID' => 'TRY']);
        $this->append($doc, $monetary, 'cbc:TaxInclusiveAmount', $this->amount($sale->grandTotal), ['currencyID' => 'TRY']);
        $this->append($doc, $monetary, 'cbc:PayableAmount', $this->amount($sale->grandTotal), ['currencyID' => 'TRY']);
        $root->appendChild($monetary);

        return $doc->saveXML();
    }

    /**
     * Alış faturası için UBL-TR 1.2 XML (tedarikçiden gelen faturayı sistemde göstermek / saklamak için).
     */
    public function buildPurchaseInvoiceXml(Purchase $purchase): string
    {
        $purchase->load(['supplier', 'items.product']);
        $company = $this->company;
        $supplier = $purchase->supplier;

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElementNS(self::UBL_INVOICE_NS, 'Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::CBC_NS);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::CAC_NS);
        $doc->appendChild($root);

        $this->append($doc, $root, 'cbc:CustomizationID', 'TR1.2');
        $this->append($doc, $root, 'cbc:ProfileID', 'TICARIFATURA');
        $this->append($doc, $root, 'cbc:ID', $purchase->purchaseNumber);
        $this->append($doc, $root, 'cbc:IssueDate', $purchase->purchaseDate->format('Y-m-d'));
        $this->append($doc, $root, 'cbc:IssueTime', $purchase->purchaseDate->format('H:i:s'));
        $this->append($doc, $root, 'cbc:DocumentTypeCode', $purchase->isReturn ? 'IADE' : 'ALIS');
        $this->append($doc, $root, 'cbc:DocumentCurrencyCode', 'TRY');

        // Satıcı (tedarikçi)
        $supplierParty = $this->createParty($doc, $supplier?->name ?? 'Tedarikçi', $supplier?->address, $supplier?->taxNumber, $supplier?->taxOffice, $supplier?->phone, $supplier?->email, true);
        $accountSupplier = $doc->createElementNS(self::CAC_NS, 'cac:AccountingSupplierParty');
        $accountSupplier->appendChild($supplierParty);
        $root->appendChild($accountSupplier);

        // Alıcı (biz)
        $customerParty = $this->createParty($doc, $company->name, $company->address, $company->taxNumber, $company->taxOffice, $company->phone, $company->email, false);
        $accountCustomer = $doc->createElementNS(self::CAC_NS, 'cac:AccountingCustomerParty');
        $accountCustomer->appendChild($customerParty);
        $root->appendChild($accountCustomer);

        foreach ($purchase->items as $index => $item) {
            $line = $this->createInvoiceLine($doc, $index + 1, $item->product?->name ?? 'Ürün', $item->quantity, $item->unitPrice, $item->kdvRate ?? 18, $item->lineTotal, $purchase->kdvIncluded ?? true);
            $root->appendChild($line);
        }

        $taxTotal = $doc->createElementNS(self::CAC_NS, 'cac:TaxTotal');
        $this->append($doc, $taxTotal, 'cbc:TaxAmount', $this->amount($purchase->kdvTotal), ['currencyID' => 'TRY']);
        $root->appendChild($taxTotal);

        $monetary = $doc->createElementNS(self::CAC_NS, 'cac:LegalMonetaryTotal');
        $this->append($doc, $monetary, 'cbc:LineExtensionAmount', $this->amount($purchase->subtotal), ['currencyID' => 'TRY']);
        $this->append($doc, $monetary, 'cbc:TaxExclusiveAmount', $this->amount($purchase->subtotal), ['currencyID' => 'TRY']);
        $this->append($doc, $monetary, 'cbc:TaxInclusiveAmount', $this->amount($purchase->grandTotal), ['currencyID' => 'TRY']);
        $this->append($doc, $monetary, 'cbc:PayableAmount', $this->amount($purchase->grandTotal), ['currencyID' => 'TRY']);
        $root->appendChild($monetary);

        return $doc->saveXML();
    }

    private function createParty(DOMDocument $doc, string $name, ?string $address, ?string $taxNumber, ?string $taxOffice, ?string $phone, ?string $email, bool $isSupplier): DOMElement
    {
        $party = $doc->createElementNS(self::CAC_NS, 'cac:Party');

        $partyName = $doc->createElementNS(self::CAC_NS, 'cac:PartyName');
        $this->append($doc, $partyName, 'cbc:Name', $name);
        $party->appendChild($partyName);

        if ($address !== null && $address !== '') {
            $addr = $doc->createElementNS(self::CAC_NS, 'cac:PostalAddress');
            $this->append($doc, $addr, 'cbc:StreetName', $address);
            $party->appendChild($addr);
        }

        if ($taxNumber !== null && $taxNumber !== '') {
            $taxScheme = $doc->createElementNS(self::CAC_NS, 'cac:PartyTaxScheme');
            $this->append($doc, $taxScheme, 'cbc:CompanyID', $taxNumber);
            if ($taxOffice) {
                $this->append($doc, $taxScheme, 'cbc:TaxScheme', $taxOffice);
            }
            $party->appendChild($taxScheme);
        }

        $contact = $doc->createElementNS(self::CAC_NS, 'cac:Contact');
        if ($phone) {
            $this->append($doc, $contact, 'cbc:Telephone', $phone);
        }
        if ($email) {
            $this->append($doc, $contact, 'cbc:ElectronicMail', $email);
        }
        $party->appendChild($contact);

        return $party;
    }

    /**
     * UBL-TR: LineExtensionAmount = matrah (KDV hariç). Birim fiyat matrah ile uyumlu (net).
     * Satır bazında KDV kategorisi (TaxCategory) eklenir.
     */
    private function createInvoiceLine(DOMDocument $doc, int $id, string $name, int $quantity, $unitPrice, $kdvRate, $lineTotal, bool $kdvIncluded): DOMElement
    {
        $rate = (float) $kdvRate;
        $total = (float) $lineTotal;
        $qty = (int) $quantity;
        $lineNet = round($total / (1 + $rate / 100), 2);
        $unitNet = $qty > 0 ? round($lineNet / $qty, 2) : 0;

        $line = $doc->createElementNS(self::CAC_NS, 'cac:InvoiceLine');
        $this->append($doc, $line, 'cbc:ID', (string) $id);
        $this->append($doc, $line, 'cbc:InvoicedQuantity', (string) $qty, ['unitCode' => 'C62']);
        $this->append($doc, $line, 'cbc:LineExtensionAmount', $this->amount($lineNet), ['currencyID' => 'TRY']);

        $item = $doc->createElementNS(self::CAC_NS, 'cac:Item');
        $this->append($doc, $item, 'cbc:Name', $name);
        $taxCat = $doc->createElementNS(self::CAC_NS, 'cac:ClassifiedTaxCategory');
        $this->append($doc, $taxCat, 'cbc:Name', 'KDV');
        $this->append($doc, $taxCat, 'cbc:Percent', $this->amount($rate));
        $taxScheme = $doc->createElementNS(self::CAC_NS, 'cac:TaxScheme');
        $this->append($doc, $taxScheme, 'cbc:Name', 'KDV');
        $taxCat->appendChild($taxScheme);
        $item->appendChild($taxCat);
        $line->appendChild($item);

        $price = $doc->createElementNS(self::CAC_NS, 'cac:Price');
        $this->append($doc, $price, 'cbc:PriceAmount', $this->amount($unitNet), ['currencyID' => 'TRY']);
        $this->append($doc, $price, 'cbc:BaseQuantity', '1', ['unitCode' => 'C62']);
        $line->appendChild($price);

        return $line;
    }

    private function append(DOMDocument $doc, \DOMNode $parent, string $tag, string $value, array $attrs = []): void
    {
        $parts = explode(':', $tag, 2);
        $ns = count($parts) === 2 ? ($parts[0] === 'cbc' ? self::CBC_NS : self::CAC_NS) : null;
        $localName = $parts[1] ?? $parts[0];
        $el = $ns ? $doc->createElementNS($ns, $tag) : $doc->createElement($tag);
        $el->appendChild($doc->createTextNode($value));
        foreach ($attrs as $k => $v) {
            $el->setAttribute($k, $v);
        }
        $parent->appendChild($el);
    }

    private function amount($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    /**
     * E-fatura gönderimi. Entegratör veya GİB endpoint'i ayarlıysa HTTP ile gönderir; yoksa sadece durumu günceller ve XML üretir.
     */
    public function sendSaleEInvoice(Sale $sale): array
    {
        if ($sale->isCancelled) {
            return ['success' => false, 'message' => 'İptal edilmiş satış için e-fatura gönderilemez.'];
        }

        $company = $this->company;
        if (!$company || !$company->efaturaEndpoint) {
            return ['success' => false, 'message' => 'E-fatura entegrasyonu ayarlanmamış. Ayarlar > E-Fatura bölümünden endpoint ve kullanıcı bilgilerini girin.'];
        }

        $xml = $this->buildSaleInvoiceXml($sale);

        try {
            $response = $this->sendToProvider($xml, $company);
            $sale->efaturaStatus = $response['status'] ?? 'sent';
            $sale->efaturaSentAt = now();
            $sale->efaturaEnvelopeId = $response['envelopeId'] ?? null;
            $sale->efaturaResponse = is_string($response['raw'] ?? null) ? $response['raw'] : json_encode($response);
            $sale->saveQuietly();

            return ['success' => true, 'message' => 'E-fatura gönderildi.', 'envelopeId' => $sale->efaturaEnvelopeId];
        } catch (\Throwable $e) {
            Log::warning('E-fatura gönderim hatası: ' . $e->getMessage(), ['sale' => $sale->id]);
            $sale->efaturaStatus = 'rejected';
            $sale->efaturaResponse = $e->getMessage();
            $sale->saveQuietly();
            return ['success' => false, 'message' => 'Gönderim hatası: ' . $e->getMessage()];
        }
    }

    public function sendPurchaseEInvoice(Purchase $purchase): array
    {
        if ($purchase->isCancelled) {
            return ['success' => false, 'message' => 'İptal edilmiş alış için e-fatura gönderilemez.'];
        }

        $company = $this->company;
        if (!$company || !$company->efaturaEndpoint) {
            return ['success' => false, 'message' => 'E-fatura entegrasyonu ayarlanmamış. Ayarlar > E-Fatura bölümünden endpoint ve kullanıcı bilgilerini girin.'];
        }

        $xml = $this->buildPurchaseInvoiceXml($purchase);

        try {
            $response = $this->sendToProvider($xml, $company);
            $purchase->efaturaStatus = $response['status'] ?? 'sent';
            $purchase->efaturaSentAt = now();
            $purchase->efaturaEnvelopeId = $response['envelopeId'] ?? null;
            $purchase->efaturaResponse = is_string($response['raw'] ?? null) ? $response['raw'] : json_encode($response);
            $purchase->saveQuietly();

            return ['success' => true, 'message' => 'E-fatura gönderildi.', 'envelopeId' => $purchase->efaturaEnvelopeId];
        } catch (\Throwable $e) {
            Log::warning('E-fatura (alış) gönderim hatası: ' . $e->getMessage(), ['purchase' => $purchase->id]);
            $purchase->efaturaStatus = 'rejected';
            $purchase->efaturaResponse = $e->getMessage();
            $purchase->saveQuietly();
            return ['success' => false, 'message' => 'Gönderim hatası: ' . $e->getMessage()];
        }
    }

    /**
     * Entegratör API'sine XML gönderir. Provider'a göre (fitbulut, gib, generic) farklı format kullanılabilir.
     */
    private function sendToProvider(string $xml, Company $company): array
    {
        $endpoint = rtrim($company->efaturaEndpoint, '/');
        $username = $company->efaturaUsername;
        $password = $company->efaturaPassword;

        // Genel entegratör: POST ile XML body veya multipart
        $response = Http::withBasicAuth($username ?? '', $password ?? '')
            ->withHeaders(['Content-Type' => 'application/xml', 'Accept' => 'application/json'])
            ->timeout(30)
            ->withBody($xml, 'application/xml')
            ->post($endpoint . '/invoice/send');

        if ($response->successful()) {
            $body = $response->json();
            return [
                'status' => $body['status'] ?? 'sent',
                'envelopeId' => $body['envelopeId'] ?? $body['uuid'] ?? null,
                'raw' => $response->body(),
            ];
        }

        throw new \RuntimeException($response->body() ?: 'HTTP ' . $response->status());
    }
}
