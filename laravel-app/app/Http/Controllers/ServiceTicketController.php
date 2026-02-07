<?php

namespace App\Http\Controllers;

use App\Models\ServiceTicket;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceTicketController extends Controller
{
    public function index()
    {
        $tickets = ServiceTicket::with(['sale', 'customer'])->orderBy('createdAt', 'desc')->paginate(20);
        return view('service-tickets.index', compact('tickets'));
    }

    public function show(ServiceTicket $serviceTicket)
    {
        $serviceTicket->load(['sale', 'customer', 'assignedUser', 'details']);
        return view('service-tickets.show', compact('serviceTicket'));
    }

    public function create()
    {
        $sales = Sale::with('customer')->orderBy('createdAt', 'desc')->take(100)->get();
        $users = User::where('isActive', true)->orderBy('name')->get();
        return view('service-tickets.create', compact('sales', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'saleId' => 'required|exists:sales,id',
            'customerId' => 'required|exists:customers,id',
            'issueType' => 'required|string|max:255',
            'description' => 'nullable|string',
            'underWarranty' => 'boolean',
            'assignedUserId' => 'nullable|exists:users,id',
        ]);
        $sale = Sale::findOrFail($validated['saleId']);
        $ticketNumber = 'SSH-' . date('Y') . '-' . str_pad((string) (ServiceTicket::whereYear('createdAt', date('Y'))->count() + 1), 5, '0', STR_PAD_LEFT);
        ServiceTicket::create([
            'ticketNumber' => $ticketNumber,
            'saleId' => $validated['saleId'],
            'customerId' => $validated['customerId'],
            'status' => 'acildi',
            'underWarranty' => $request->boolean('underWarranty'),
            'issueType' => $validated['issueType'],
            'description' => $validated['description'] ?? null,
            'assignedUserId' => $validated['assignedUserId'] ?? null,
            'openedAt' => now(),
        ]);
        return redirect()->route('service-tickets.index')->with('success', 'Servis kaydı oluşturuldu.');
    }
}
