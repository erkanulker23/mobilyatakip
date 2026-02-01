import { DocumentCheckIcon } from '@heroicons/react/24/outline';
import { PageHeader, Card } from '../../components/ui';

export default function StatementsPage() {
  return (
    <div className="space-y-6">
      <PageHeader
        title="Mutabakat"
        description="Tedarikçi mutabakatları ve PDF çıktı"
        icon={DocumentCheckIcon}
      />
      <Card>
        <p className="text-zinc-600">Tedarikçi mutabakatları ve PDF çıktı bu sayfada yönetilecektir.</p>
      </Card>
    </div>
  );
}
