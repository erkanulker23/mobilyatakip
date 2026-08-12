<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Models\ServiceTicketDetail;
use App\Models\ServicePart;
use App\Models\ShippingCompanyPayment;
use App\Models\Sale;
use App\Models\Branch;
use App\Models\ShippingCompany;
use App\Models\ShippingCompanyVehicle;
use App\Models\User;
use App\Models\Customer;
use App\Support\ServiceTicketStatus;
use App\Support\SaleDelivery;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceTicketController extends Controller
{
    public function __construct(private AuditService $auditService) {}

    public function index(Request $request)
    {
        $q = ServiceTicket::with([
            'sale',
            'customer',
            'branch',
            'openingDetail.user',
            'closingDetail.user',
            'legacyClosingDetail.user',
        ])->orderBy('createdAt', 'desc');
        $this->applyIndexFilters($q, $request, true);
        $tickets = $q->paginate(20)->withQueryString();
        $customers = Customer::with(['city', 'district'])->where('isActive', true)->orderBy('name')->get();
        $branches = Branch::forSelect(false);

        $statusCounts = $this->applyIndexFilters(ServiceTicket::query(), $request, false)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();
        $openCount = collect($statusCounts)
            ->reject(fn ($_, $status) => ServiceTicketStatus::isClosed($status))
            ->sum();
        $stats = [
            'open' => $openCount,
            'parca_bekleniyor' => (int) ($statusCounts['parca_bekleniyor'] ?? 0),
            'sevkiyatci_bekleniyor' => (int) ($statusCounts['sevkiyatci_bekleniyor'] ?? 0),
            'tamamlandi' => (int) ($statusCounts['tamamlandi'] ?? 0),
            'total' => array_sum($statusCounts),
        ];

        return view('service-tickets.index', compact('tickets', 'customers', 'branches', 'stats'));
    }

    public function show(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load([
            'sale',
            'customer.city',
            'customer.district',
            'branch',
            'assignedUser',
            'shippingCompany',
            'shippingVehicle',
            'openingDetail.user',
            'closingDetail.user',
            'legacyClosingDetail.user',
            'workshopFinishedDetail.user',
            'details.user',
        ]);

        return view('service-tickets.show', compact('serviceTicket'));
    }

    public function print(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load(['sale.customer.city', 'sale.customer.district', 'customer.city', 'customer.district', 'branch', 'assignedUser', 'shippingCompany', 'shippingVehicle', 'details.user']);

        return view('service-tickets.print', compact('serviceTicket'));
    }

    public function create(Request $request)
    {
        if ($request->user()?->isWorkshop()) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }

        $customers = Customer::with(['city', 'district'])->where('isActive', true)->orderBy('name')->get();
        $selectedCustomerId = old('customerId', request('customerId'));
        $selectedSaleId = old('saleId', request('saleId'));
        $shippingFormData = $this->shippingFormData();
        $branches = Branch::forSelect();

        return view('service-tickets.create', compact('customers', 'selectedCustomerId', 'selectedSaleId', 'branches') + $shippingFormData);
    }

    public function store(Request $request)
    {
        if ($request->user()?->isWorkshop()) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }

        $request->merge([
            'problems' => array_values(array_filter(
                (array) $request->input('problems', []),
                fn ($p) => trim((string) $p) !== ''
            )),
        ]);
        if ($request->filled('serviceChargeAmount')) {
            $request->merge(['serviceChargeAmount' => money_parse($request->input('serviceChargeAmount'))]);
        }

        $rules = [
            'saleId' => 'nullable|exists:sales,id',
            'customerId' => 'required|exists:customers,id',
            'branchId' => 'nullable|exists:branches,id',
            'problems' => 'required|array|min:1',
            'problems.*' => 'required|string|max:500',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'dueDate' => 'nullable|date',
            'underWarranty' => 'boolean',
            'assignedUserId' => 'nullable|exists:users,id',
            'assignedVehiclePlate' => 'nullable|string|max:20',
            'assignedDriverName' => 'nullable|string|max:100',
            'assignedDriverPhone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+][0-9\s\-()]{9,24}$/'],
            'shippingCompanyId' => 'nullable|exists:shipping_companies,id',
            'shippingVehicleId' => [
                'nullable',
                'exists:shipping_company_vehicles,id',
                Rule::exists('shipping_company_vehicles', 'id')->where(fn ($q) => $request->filled('shippingCompanyId')
                    ? $q->where('shippingCompanyId', $request->input('shippingCompanyId'))
                    : $q),
            ],
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ];
        if (! $request->boolean('underWarranty')) {
            $rules['serviceChargeAmount'] = 'required|numeric|min:0';
        } else {
            $rules['serviceChargeAmount'] = 'nullable|numeric|min:0';
        }
        $validated = $request->validate($rules, [
            'serviceChargeAmount.required' => 'Garanti kapsamında değilse servis ücreti girilmelidir.',
            'problems.required' => 'En az bir müşteri problemi girilmelidir.',
            'problems.min' => 'En az bir müşteri problemi girilmelidir.',
            'assignedDriverPhone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);

        if (! empty($validated['saleId'])) {
            $sale = Sale::findOrFail($validated['saleId']);
            if ($sale->customerId !== $validated['customerId']) {
                return back()->withInput()->with('error', 'Seçilen sipariş bu müşteriye ait değil.');
            }
            if (empty($validated['branchId']) && $sale->branchId) {
                $validated['branchId'] = $sale->branchId;
            }
        }

        $reportedProblems = ServiceTicketStatus::normalizeProblems(
            collect($validated['problems'])->map(fn ($description) => ['description' => $description])->all()
        );
        if ($reportedProblems === []) {
            return back()->withInput()->with('error', 'En az bir müşteri problemi girilmelidir.');
        }

        $ticketNumber = 'SSH-' . date('Y') . '-' . str_pad(
            (string) (ServiceTicket::whereYear('createdAt', date('Y'))->count() + 1),
            5,
            '0',
            STR_PAD_LEFT
        );

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('service-tickets', 'public');
                $images[] = '/storage/' . $path;
            }
        }

        $validated = $this->resolveShippingAssignment($validated);

        $ticket = DB::transaction(function () use ($validated, $request, $ticketNumber, $reportedProblems, $images) {
            $ticket = ServiceTicket::create([
                'ticketNumber' => $ticketNumber,
                'saleId' => $validated['saleId'] ?? null,
                'customerId' => $validated['customerId'],
                'branchId' => $validated['branchId'] ?? null,
                'status' => 'acildi',
                'underWarranty' => $request->boolean('underWarranty'),
                'issueType' => $reportedProblems[0]['description'],
                'description' => $validated['description'] ?? null,
                'notes' => isset($validated['notes']) ? trim((string) $validated['notes']) : null,
                'dueDate' => $validated['dueDate'] ?? null,
                'reportedProblems' => $reportedProblems,
                'assignedUserId' => $validated['assignedUserId'] ?? null,
                'assignedVehiclePlate' => $validated['assignedVehiclePlate'] ?? null,
                'assignedDriverName' => $validated['assignedDriverName'] ?? null,
                'assignedDriverPhone' => $validated['assignedDriverPhone'] ?? null,
                'shippingCompanyId' => $validated['shippingCompanyId'] ?? null,
                'shippingVehicleId' => $validated['shippingVehicleId'] ?? null,
                'openedAt' => now(),
                'images' => $images,
                'serviceChargeAmount' => $request->boolean('underWarranty') ? null : ($validated['serviceChargeAmount'] ?? 0),
            ]);

            ServiceTicketDetail::create([
                'ticketId' => $ticket->id,
                'userId' => auth()->id() ?: null,
                'action' => 'acildi',
                'actionDate' => now(),
                'notes' => count($reportedProblems) . ' problem kaydedildi.',
            ]);

            return $ticket;
        });

        $this->auditService->logCreate('service_ticket', $ticket->id, ['ticketNumber' => $ticket->ticketNumber]);

        if (! empty($ticket->saleId)) {
            $sale = Sale::find($ticket->saleId);
            if ($sale) {
                SaleDelivery::syncFromServiceTickets($sale);
            }
        }

        return redirect()->route('service-tickets.index')->with('success', 'Servis kaydı oluşturuldu: ' . $ticket->ticketNumber);
    }

    public function edit(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load([
            'sale.customer',
            'customer',
            'branch',
            'openingDetail.user',
            'closingDetail.user',
            'legacyClosingDetail.user',
            'workshopFinishedDetail.user',
            'details.user',
        ]);
        $sales = Sale::with(['customer', 'branch'])->orderBy('createdAt', 'desc')->take(100)->get();
        $users = User::where('isActive', true)->orderBy('name')->get();
        $shippingFormData = $this->shippingFormData();
        $branches = Branch::forSelect();
        if ($serviceTicket->branchId && ! $branches->contains('id', $serviceTicket->branchId) && $serviceTicket->branch) {
            $branches = $branches->prepend($serviceTicket->branch);
        }

        return view('service-tickets.edit', compact('serviceTicket', 'sales', 'users', 'branches') + $shippingFormData);
    }

    public function update(Request $request, ServiceTicket $serviceTicket)
    {
        if ($request->user()?->hideCommercialData()) {
            return $this->updateForWorkshop($request, $serviceTicket);
        }

        if ($request->filled('serviceChargeAmount')) {
            $request->merge(['serviceChargeAmount' => money_parse($request->input('serviceChargeAmount'))]);
        }

        $validated = $request->validate([
            'saleId' => 'nullable|exists:sales,id',
            'customerId' => 'required|exists:customers,id',
            'branchId' => 'nullable|exists:branches,id',
            'problems' => 'required|array|min:1',
            'problems.*.description' => 'required|string|max:500',
            'problems.*.status' => 'nullable|in:bekliyor,duzeltildi,duzeltilemedi',
            'description' => 'nullable|string',
            'dueDate' => 'nullable|date',
            'status' => 'nullable|' . ServiceTicketStatus::validationRule(),
            'underWarranty' => 'nullable|boolean',
            'assignedUserId' => 'nullable|exists:users,id',
            'assignedVehiclePlate' => 'nullable|string|max:20',
            'assignedDriverName' => 'nullable|string|max:100',
            'assignedDriverPhone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+][0-9\s\-()]{9,24}$/'],
            'shippingCompanyId' => 'nullable|exists:shipping_companies,id',
            'shippingVehicleId' => [
                'nullable',
                'exists:shipping_company_vehicles,id',
                Rule::exists('shipping_company_vehicles', 'id')->where(fn ($q) => $request->filled('shippingCompanyId')
                    ? $q->where('shippingCompanyId', $request->input('shippingCompanyId'))
                    : $q),
            ],
            'notes' => 'nullable|string',
            'serviceChargeAmount' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'removeImages' => 'nullable|array',
            'removeImages.*' => 'string|max:500',
            'newStages' => 'nullable|array',
            'newStages.*' => 'nullable|string|max:1000',
            'closeTicket' => 'nullable|boolean',
            'reopenTicket' => 'nullable|boolean',
        ], [
            'assignedDriverPhone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
            'problems.required' => 'En az bir müşteri problemi girilmelidir.',
        ]);

        if (! empty($validated['saleId'])) {
            $sale = Sale::findOrFail($validated['saleId']);
            if ($sale->customerId !== $validated['customerId']) {
                return back()->withInput()->with('error', 'Seçilen sipariş bu müşteriye ait değil.');
            }
        }

        $reportedProblems = ServiceTicketStatus::normalizeProblems($validated['problems']);
        if ($reportedProblems === []) {
            return back()->withInput()->with('error', 'En az bir müşteri problemi girilmelidir.');
        }

        $validated['underWarranty'] = $request->boolean('underWarranty');
        $validated['reportedProblems'] = $reportedProblems;
        $validated['issueType'] = $reportedProblems[0]['description'];
        unset($validated['problems']);

        $oldStatus = $serviceTicket->status ?? 'acildi';
        if ($request->boolean('closeTicket') && ! ServiceTicketStatus::isClosed($oldStatus)) {
            $validated['status'] = 'tamamlandi';
        } elseif ($request->boolean('reopenTicket') && ServiceTicketStatus::isClosed($oldStatus)) {
            $validated['status'] = 'devam_ediyor';
            $validated['closedAt'] = null;
        }

        $newStatus = $validated['status'] ?? $oldStatus;
        if ($newStatus === 'tamamlandi' && ! $serviceTicket->closedAt) {
            $validated['closedAt'] = now();
        } elseif (in_array($newStatus, ServiceTicketStatus::openStatuses(), true)) {
            $validated['closedAt'] = null;
        }

        $newStages = collect($validated['newStages'] ?? [])
            ->map(fn ($note) => trim((string) $note))
            ->filter()
            ->values()
            ->all();
        unset($validated['newStages'], $validated['closeTicket'], $validated['reopenTicket']);

        $validated = $this->resolveShippingAssignment($validated);

        $images = (array) ($serviceTicket->images ?? []);
        $removeImages = collect($validated['removeImages'] ?? [])->filter()->values()->all();
        unset($validated['removeImages']);
        if ($removeImages !== []) {
            $images = array_values(array_filter($images, function ($path) use ($removeImages) {
                if (in_array($path, $removeImages, true)) {
                    $this->deleteServiceTicketImage($path);

                    return false;
                }

                return true;
            }));
        }
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('service-tickets', 'public');
                $images[] = '/storage/' . $path;
            }
        }
        $validated['images'] = $images;

        $oldProblems = ServiceTicketStatus::normalizeProblems($serviceTicket->reportedProblems ?? []);
        $oldSaleId = $serviceTicket->saleId;
        $serviceTicket->update($validated);
        $serviceTicket->refresh();

        $this->logProblemChanges($serviceTicket, $oldProblems, $reportedProblems);

        foreach ($newStages as $stageNote) {
            ServiceTicketDetail::create([
                'ticketId' => $serviceTicket->id,
                'userId' => auth()->id() ?: null,
                'action' => 'asama',
                'actionDate' => now(),
                'notes' => $stageNote,
            ]);
        }

        if ($newStages !== [] && ($serviceTicket->status ?? 'acildi') === 'acildi') {
            $serviceTicket->update(['status' => 'devam_ediyor']);
            $serviceTicket->refresh();
        }

        $currentStatus = $serviceTicket->status ?? 'acildi';
        if ($oldStatus !== $currentStatus) {
            ServiceTicketDetail::create([
                'ticketId' => $serviceTicket->id,
                'userId' => auth()->id() ?: null,
                'action' => ServiceTicketStatus::isClosed($currentStatus) ? 'kapatildi' : 'durum_guncelleme',
                'actionDate' => now(),
                'notes' => 'Durum: ' . ServiceTicketStatus::label($currentStatus),
            ]);
        }

        $this->syncLinkedSaleDelivery($oldSaleId, $serviceTicket->saleId);

        $this->auditService->logUpdate('service_ticket', $serviceTicket->id, [], [
            'ticketNumber' => $serviceTicket->ticketNumber,
        ]);

        $message = 'Servis kaydı güncellendi.';
        if ($newStages !== []) {
            $message .= ' ' . count($newStages) . ' aşama eklendi.';
        }
        if ($currentStatus === 'tamamlandi' && $oldStatus !== 'tamamlandi') {
            $message = 'SSH kaydı kapatıldı.';
        }

        return redirect()->route('service-tickets.show', $serviceTicket)->with('success', $message);
    }

    private function updateForWorkshop(Request $request, ServiceTicket $serviceTicket)
    {
        $validated = $request->validate([
            'newStages' => 'nullable|array',
            'newStages.*' => 'nullable|string|max:1000',
            'markWorkshopFinished' => 'nullable|boolean',
            'workshopFinishedNotes' => 'nullable|string|max:1000',
        ]);

        if ($request->boolean('markWorkshopFinished')) {
            $result = $this->addWorkshopFinishedStage(
                $serviceTicket,
                $validated['workshopFinishedNotes'] ?? null
            );
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                return $result;
            }
        }

        $newStages = collect($validated['newStages'] ?? [])
            ->map(fn ($note) => trim((string) $note))
            ->filter()
            ->values()
            ->all();

        foreach ($newStages as $stageNote) {
            ServiceTicketDetail::create([
                'ticketId' => $serviceTicket->id,
                'userId' => auth()->id() ?: null,
                'action' => 'asama',
                'actionDate' => now(),
                'notes' => $stageNote,
            ]);
        }

        if ($newStages !== [] && ($serviceTicket->status ?? 'acildi') === 'acildi') {
            $serviceTicket->update(['status' => 'devam_ediyor']);
        }

        $message = $newStages !== []
            ? count($newStages) . ' aşama eklendi.'
            : 'Kayıt güncellendi.';

        if ($request->boolean('markWorkshopFinished')) {
            $message = 'Atölyede iş bitti aşaması eklendi.';
            if ($newStages !== []) {
                $message .= ' ' . count($newStages) . ' ek aşama da kaydedildi.';
            }
        }

        return redirect()->route('service-tickets.show', $serviceTicket)->with('success', $message);
    }

    public function markWorkshopFinished(Request $request, ServiceTicket $serviceTicket)
    {
        $this->authorizeWorkshopStage($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $result = $this->addWorkshopFinishedStage($serviceTicket, $validated['notes'] ?? null);
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        return redirect()
            ->route('service-tickets.show', $serviceTicket)
            ->with('success', 'Atölyede iş bitti aşaması eklendi.');
    }

    private function authorizeWorkshopStage(Request $request): void
    {
        $user = $request->user();
        if (! $user || (! $user->isWorkshop() && ! $user->isAdmin())) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }

    private function addWorkshopFinishedStage(ServiceTicket $serviceTicket, ?string $notes = null): ?\Illuminate\Http\RedirectResponse
    {
        if (ServiceTicketStatus::isClosed($serviceTicket->status ?? '')) {
            return redirect()
                ->back()
                ->with('error', 'Kapalı servis kayıtlarına aşama eklenemez.');
        }

        if ($serviceTicket->isWorkshopFinished()) {
            return redirect()
                ->back()
                ->with('info', 'Bu kayıt için atölyede iş bitti aşaması zaten eklenmiş.');
        }

        $note = trim((string) $notes);
        if ($note === '') {
            $note = ServiceTicketStatus::WORKSHOP_FINISHED_NOTE;
        }

        DB::transaction(function () use ($serviceTicket, $note) {
            ServiceTicketDetail::create([
                'ticketId' => $serviceTicket->id,
                'userId' => auth()->id() ?: null,
                'action' => ServiceTicketStatus::ACTION_WORKSHOP_FINISHED,
                'actionDate' => now(),
                'notes' => $note,
            ]);

            if (($serviceTicket->status ?? 'acildi') === 'acildi') {
                $serviceTicket->update(['status' => 'devam_ediyor']);
            }
        });

        return null;
    }

    public function updateProblemStatus(Request $request, ServiceTicket $serviceTicket)
    {
        $validated = $request->validate([
            'problemIndex' => 'required|integer|min:0',
            'status' => 'required|in:bekliyor,duzeltildi,duzeltilemedi',
        ]);

        $problems = ServiceTicketStatus::normalizeProblems($serviceTicket->reportedProblems ?? []);
        $index = (int) $validated['problemIndex'];
        if (! array_key_exists($index, $problems)) {
            return back()->with('error', 'Problem bulunamadı.');
        }

        $oldStatus = $problems[$index]['status'];
        $newStatus = $validated['status'];
        if ($oldStatus === $newStatus) {
            return back();
        }

        $problems[$index]['status'] = $newStatus;
        $ticketStatus = $serviceTicket->status ?? 'acildi';
        if ($newStatus !== 'bekliyor' && $ticketStatus === 'acildi') {
            $ticketStatus = 'devam_ediyor';
        }

        $allFixed = collect($problems)->every(fn ($p) => $p['status'] === 'duzeltildi');
        $closedAt = $serviceTicket->closedAt;
        $wasClosed = ServiceTicketStatus::isClosed($serviceTicket->status ?? '');
        if ($allFixed) {
            $ticketStatus = 'tamamlandi';
            $closedAt = $closedAt ?? now();
        }

        DB::transaction(function () use ($serviceTicket, $problems, $ticketStatus, $closedAt, $index, $newStatus, $wasClosed) {
            $serviceTicket->update([
                'reportedProblems' => $problems,
                'status' => $ticketStatus,
                'closedAt' => $closedAt,
            ]);

            ServiceTicketDetail::create([
                'ticketId' => $serviceTicket->id,
                'userId' => auth()->id() ?: null,
                'action' => 'problem_durumu',
                'actionDate' => now(),
                'notes' => ($index + 1) . '. problem: ' . ServiceTicketStatus::problemLabel($newStatus) . ' — ' . $problems[$index]['description'],
            ]);

            if ($ticketStatus === 'tamamlandi' && ! $wasClosed) {
                ServiceTicketDetail::create([
                    'ticketId' => $serviceTicket->id,
                    'userId' => auth()->id() ?: null,
                    'action' => 'kapatildi',
                    'actionDate' => now(),
                    'notes' => 'Durum: ' . ServiceTicketStatus::label($ticketStatus),
                ]);
            }
        });

        $this->auditService->logAction('service_ticket', $serviceTicket->id, 'status', [
            'ticketNumber' => $serviceTicket->ticketNumber,
            'status' => $newStatus,
        ]);

        $serviceTicket->refresh();
        $this->syncLinkedSaleDelivery(null, $serviceTicket->saleId);

        return back()->with('success', 'Problem durumu güncellendi.');
    }

    public function updateStatus(Request $request, ServiceTicket $serviceTicket)
    {
        $validated = $request->validate([
            'status' => 'required|' . ServiceTicketStatus::validationRule(),
        ]);

        $status = $validated['status'];
        $oldStatus = $serviceTicket->status ?? 'acildi';
        $updates = ['status' => $status];

        if ($status === 'tamamlandi') {
            $updates['closedAt'] = $serviceTicket->closedAt ?? now();
        } elseif (in_array($status, ServiceTicketStatus::openStatuses(), true)) {
            $updates['closedAt'] = null;
        } elseif ($status === 'iptal' && ! $serviceTicket->closedAt) {
            $updates['closedAt'] = now();
        }

        $serviceTicket->update($updates);

        if ($oldStatus !== $status) {
            ServiceTicketDetail::create([
                'ticketId' => $serviceTicket->id,
                'userId' => auth()->id() ?: null,
                'action' => ServiceTicketStatus::isClosed($status) ? 'kapatildi' : 'durum_guncelleme',
                'actionDate' => now(),
                'notes' => 'Durum: ' . ServiceTicketStatus::label($status),
            ]);
        }

        $this->auditService->logAction('service_ticket', $serviceTicket->id, 'status', [
            'ticketNumber' => $serviceTicket->ticketNumber,
            'status' => $status,
        ]);

        if ($serviceTicket->saleId) {
            $sale = Sale::find($serviceTicket->saleId);
            if ($sale) {
                SaleDelivery::syncFromServiceTickets($sale);
            }
        }

        return back()->with('success', 'Servis durumu güncellendi.');
    }

    public function destroy(ServiceTicket $serviceTicket)
    {
        $ticketId = $serviceTicket->id;
        $ticketNumber = $serviceTicket->ticketNumber;
        $saleId = $serviceTicket->saleId;

        DB::transaction(function () use ($serviceTicket) {
            $detailIds = $serviceTicket->details()->pluck('id');
            if ($detailIds->isNotEmpty()) {
                ServicePart::whereIn('detailId', $detailIds)->delete();
                ServiceTicketDetail::whereIn('id', $detailIds)->delete();
            }

            ShippingCompanyPayment::where('serviceTicketId', $serviceTicket->id)->update(['serviceTicketId' => null]);

            foreach ((array) ($serviceTicket->images ?? []) as $image) {
                $this->deleteServiceTicketImage($image);
            }

            $serviceTicket->delete();
        });

        $this->auditService->logDelete('service_ticket', $ticketId, ['ticketNumber' => $ticketNumber]);

        if ($saleId) {
            $sale = Sale::find($saleId);
            if ($sale) {
                SaleDelivery::syncFromServiceTickets($sale);
            }
        }

        return redirect()->route('service-tickets.index')->with('success', 'Servis kaydı silindi: ' . $ticketNumber);
    }

    private function deleteServiceTicketImage(?string $path): void
    {
        if (! $path || Str::startsWith($path, 'http')) {
            return;
        }

        $relative = ltrim(str_replace('/storage/', '', parse_url($path, PHP_URL_PATH) ?: $path), '/');
        if ($relative && Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }

    /** @param  array<int, array{description?: string, status?: string}>  $oldProblems */
    /** @param  array<int, array{description: string, status: string}>  $newProblems */
    private function logProblemChanges(ServiceTicket $ticket, array $oldProblems, array $newProblems): void
    {
        foreach ($newProblems as $i => $problem) {
            $oldStatus = $oldProblems[$i]['status'] ?? null;
            if ($oldStatus !== $problem['status']) {
                ServiceTicketDetail::create([
                    'ticketId' => $ticket->id,
                    'userId' => auth()->id() ?: null,
                    'action' => 'problem_durumu',
                    'actionDate' => now(),
                    'notes' => ($i + 1) . '. problem: ' . ServiceTicketStatus::problemLabel($problem['status']) . ' — ' . $problem['description'],
                ]);
            }
        }
    }

    /** @return array{shippingCompanies: \Illuminate\Support\Collection, vehiclesByCompany: array<string, array<int, array<string, mixed>>>} */
    private function shippingFormData(): array
    {
        $shippingCompanies = ShippingCompany::where('isActive', true)->orderBy('name')->get();
        $vehiclesByCompany = ShippingCompanyVehicle::whereIn('shippingCompanyId', $shippingCompanies->pluck('id'))
            ->where('isActive', true)
            ->orderBy('vehiclePlate')
            ->get()
            ->groupBy('shippingCompanyId')
            ->map(fn ($vehicles) => $vehicles->map(fn (ShippingCompanyVehicle $v) => [
                'id' => $v->id,
                'vehiclePlate' => $v->vehiclePlate,
                'driverName' => $v->driverName,
                'driverPhone' => $v->driverPhone,
                'label' => $v->label(),
            ])->values()->all())
            ->all();

        return compact('shippingCompanies', 'vehiclesByCompany');
    }

    /** @param  array<string, mixed>  $validated */
    private function resolveShippingAssignment(array $validated): array
    {
        if (empty($validated['shippingCompanyId'])) {
            $validated['shippingCompanyId'] = null;
            $validated['shippingVehicleId'] = null;

            return $validated;
        }

        if (empty($validated['shippingVehicleId'])) {
            return $validated;
        }

        $vehicle = ShippingCompanyVehicle::where('shippingCompanyId', $validated['shippingCompanyId'])
            ->where('isActive', true)
            ->find($validated['shippingVehicleId']);

        if ($vehicle) {
            $validated['assignedVehiclePlate'] = $vehicle->vehiclePlate;
            $validated['assignedDriverName'] = $vehicle->driverName;
            $validated['assignedDriverPhone'] = $vehicle->driverPhone;
        }

        return $validated;
    }

    private function syncLinkedSaleDelivery(?string $oldSaleId, ?string $newSaleId): void
    {
        if ($oldSaleId && $oldSaleId !== $newSaleId) {
            $oldSale = Sale::find($oldSaleId);
            if ($oldSale) {
                SaleDelivery::syncFromServiceTickets($oldSale);
            }
        }

        if ($newSaleId) {
            $sale = Sale::find($newSaleId);
            if ($sale) {
                SaleDelivery::syncFromServiceTickets($sale);
            }
        }
    }

    private function applyIndexFilters($query, Request $request, bool $applyStatus)
    {
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($w) use ($s) {
                $w->where('ticketNumber', 'like', "%{$s}%")
                    ->orWhere('issueType', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($applyStatus && $request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customerId')) {
            $query->where('customerId', $request->customerId);
        }
        if ($request->filled('branchId')) {
            if ($request->input('branchId') === 'none') {
                $query->whereNull('branchId');
            } else {
                $query->where('branchId', $request->input('branchId'));
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('createdAt', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('createdAt', '<=', $request->to);
        }

        return $query;
    }
}
