import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';
import { ROUTES } from '../config/routes';
import DashboardLayout from '../layouts/DashboardLayout';
import LoginPage from '../pages/LoginPage';
import DashboardPage from '../pages/DashboardPage';
import ProductsPage from '../pages/ProductsPage';
import ProductDetailPage from '../pages/ProductDetailPage';
import CustomersPage from '../pages/CustomersPage';
import CustomerDetailPage from '../pages/CustomerDetailPage';
import QuotesPage from '../pages/QuotesPage';
import QuoteDetailPage from '../pages/QuoteDetailPage';
import EditQuotePage from '../pages/EditQuotePage';
import CreateQuotePage from '../pages/CreateQuotePage';
import SalesPage from '../pages/SalesPage';
import SaleDetailPage from '../pages/SaleDetailPage';
import CreateSalePage from '../pages/CreateSalePage';
import SettingsPage from '../pages/SettingsPage';
import AccountingPage from '../pages/AccountingPage';
import SuppliersPage from '../pages/SuppliersPage';
import SupplierDetailPage from '../pages/SupplierDetailPage';
import ServiceTicketsPage from '../pages/ServiceTicketsPage';
import CreateServiceTicketPage from '../pages/CreateServiceTicketPage';
import ServiceTicketDetailPage from '../pages/ServiceTicketDetailPage';
import PersonnelPage from '../pages/PersonnelPage';
import NotificationsPage from '../pages/NotificationsPage';
import KasaPage from '../pages/KasaPage';
import KasaDetailPage from '../pages/KasaDetailPage';
import ExpensesPage from '../pages/ExpensesPage';
import SupplierLedgerPage from '../pages/accounting/SupplierLedgerPage';
import CustomerLedgerPage from '../pages/accounting/CustomerLedgerPage';
import StatementsPage from '../pages/accounting/StatementsPage';
import ReportsPage from '../pages/ReportsPage';
import PurchasesPage from '../pages/PurchasesPage';
import PurchaseDetailPage from '../pages/PurchaseDetailPage';
import CreatePurchasePage from '../pages/CreatePurchasePage';
import UsersPage from '../pages/UsersPage';
import OdemeAlPage from '../pages/OdemeAlPage';
import SiparisYonetimiPage from '../pages/SiparisYonetimiPage';
import GelirGiderPage from '../pages/GelirGiderPage';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const token = useAuthStore((s) => s.token);
  if (!token) return <Navigate to={ROUTES.login} replace />;
  return <>{children}</>;
}

export default function AppRoutes() {
  return (
    <Routes>
      <Route path={ROUTES.login} element={<LoginPage />} />
      <Route
        path={ROUTES.home}
        element={
          <ProtectedRoute>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<DashboardPage />} />
        <Route path="siparis-yonetimi" element={<SiparisYonetimiPage />} />
        <Route path="urunler" element={<ProductsPage />} />
        <Route path="urunler/:id" element={<ProductDetailPage />} />
        <Route path="tedarikciler" element={<SuppliersPage />} />
        <Route path="tedarikciler/:id" element={<SupplierDetailPage />} />
        <Route path="personel" element={<PersonnelPage />} />
        <Route path="musteriler" element={<CustomersPage />} />
        <Route path="musteriler/:id" element={<CustomerDetailPage />} />
        <Route path="bildirimler" element={<NotificationsPage />} />
        <Route path="teklifler" element={<QuotesPage />} />
        <Route path="teklifler/yeni" element={<CreateQuotePage />} />
        <Route path="teklifler/:id/duzenle" element={<EditQuotePage />} />
        <Route path="teklifler/:id" element={<QuoteDetailPage />} />
        <Route path="satislar" element={<SalesPage />} />
        <Route path="satislar/:id" element={<SaleDetailPage />} />
        <Route path="satislar/yeni" element={<CreateSalePage />} />
        <Route path="servis-talepleri" element={<ServiceTicketsPage />} />
        <Route path="servis-talepleri/yeni" element={<CreateServiceTicketPage />} />
        <Route path="servis-talepleri/:id" element={<ServiceTicketDetailPage />} />
        <Route path="gelir-gider" element={<GelirGiderPage />} />
        <Route path="raporlar" element={<ReportsPage />} />
        <Route path="alislar" element={<PurchasesPage />} />
        <Route path="alislar/:id" element={<PurchaseDetailPage />} />
        <Route path="alislar/yeni" element={<CreatePurchasePage />} />
        <Route path="kullanicilar" element={<UsersPage />} />
        <Route path="odeme-al" element={<OdemeAlPage />} />
        <Route path="ayarlar" element={<SettingsPage />} />
        <Route path="muhasebe" element={<AccountingPage />} />
        <Route path="muhasebe/kasa" element={<KasaPage />} />
        <Route path="muhasebe/kasa/:id" element={<KasaDetailPage />} />
        <Route path="muhasebe/masraf-cikisi" element={<ExpensesPage />} />
        <Route path="muhasebe/tedarikci-cari" element={<SupplierLedgerPage />} />
        <Route path="muhasebe/musteri-cari" element={<CustomerLedgerPage />} />
        <Route path="muhasebe/mutabakatlar" element={<StatementsPage />} />
      </Route>
      <Route path="*" element={<Navigate to={ROUTES.home} replace />} />
    </Routes>
  );
}
