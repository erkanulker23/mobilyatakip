<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceTicketController extends Controller
{
    public function index(Request $request)
    {
        $q = ServiceTicket::with(['sale', 'customer'])->orderBy('createdAt', 'desc');
        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($w) use ($s) {
                $w->where('ticketNumber', 'like', "%{$s}%")
                    ->orWhere('issueType', 'like', "%{$s}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('customerId')) {
            $q->where('customerId', $request->customerId);
        }
        if ($request->filled('from')) {
            $q->whereDate('createdAt', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('createdAt', '<=', $request->to);
        }
        $tickets = $q->paginate(20)->withQueryString();
        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        return view('service-tickets.index', compact('tickets', 'customers'));
    }

    public function show(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load(['sale', 'customer', 'assignedUser', 'details.user']);
        return view('service-tickets.show', compact('serviceTicket'));
    }

    public function print(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load(['sale.customer', 'customer', 'assignedUser', 'details.user']);
        return view('service-tickets.print', compact('serviceTicket'));
    }

    public function create()
    {
        $sales = Sale::with('customer')->orderBy('createdAt', 'desc')->take(100)->get();
        $users = User::where('isActive', true)->orderBy('name')->get();
        $customers = Customer::where('isActive', true)->orderBy('name')->get();
        return view('service-tickets.create', compact('sales', 'users', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'saleId' => 'nullable|exists:sales,id',
            'customerId' => 'required|exists:customers,id',
            'issueType' => 'required|string|max:255',
            'description' => 'nullable|string',
            'underWarranty' => 'boolean',
            'assignedUserId' => 'nullable|exists:users,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);
        if (!empty($validated['saleId'])) {
            Sale::findOrFail($validated['saleId']);
        }
        $ticketNumber = 'SSH-' . date('Y') . '-' . str_pad((string) (ServiceTicket::whereYear('createdAt', date('Y'))->count() + 1), 5, '0', STR_PAD_LEFT);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('service-tickets', 'public');
                $images[] = '/storage/' . $path;
            }
        }

        ServiceTicket::create([
            'ticketNumber' => $ticketNumber,
            'saleId' => $validated['saleId'] ?? null,
            'customerId' => $validated['customerId'],
            'status' => 'acildi',
            'underWarranty' => $request->boolean('underWarranty'),
            'issueType' => $validated['issueType'],
            'description' => $validated['description'] ?? null,
            'assignedUserId' => $validated['assignedUserId'] ?? null,
            'openedAt' => now(),
            'images' => $images,
        ]);
        return redirect()->route('service-tickets.index')->with('success', 'Servis kaydı oluşturuldu.');
    }

    public function edit(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load(['sale.customer', 'customer', 'details']);
        $sales = Sale::with('customer')->orderBy('createdAt', 'desc')->take(100)->get();
        $users = User::where('isActive', true)->orderBy('name')->get();
        return view('service-tickets.edit', compact('serviceTicket', 'sales', 'users'));
    }

    public function update(Request $request, ServiceTicket $serviceTicket)
    {
        $validated = $request->validate([
            'saleId' => 'nullable|exists:sales,id',
            'customerId' => 'required|exists:customers,id',
            'issueType' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:acildi,devam_ediyor,tamamlandi,iptal',
            'underWarranty' => 'nullable|boolean',
            'assignedUserId' => 'nullable|exists:users,id',
            'assignedVehiclePlate' => 'nullable|string|max:20',
            'assignedDriverName' => 'nullable|string|max:100',
            'assignedDriverPhone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+][0-9\s\-()]{9,19}$/'],
            'notes' => 'nullable|string',
            'serviceChargeAmount' => 'nullable|numeric|min:0',
        ], [
            'assignedDriverPhone.regex' => 'Geçerli bir telefon numarası giriniz (Örn: 0555 123 45 67)',
        ]);
        $validated['underWarranty'] = $request->boolean('underWarranty');
        $serviceTicket->update($validated);
        return redirect()->route('service-tickets.show', $serviceTicket)->with('success', 'Servis kaydı güncellendi.');
    }
}
