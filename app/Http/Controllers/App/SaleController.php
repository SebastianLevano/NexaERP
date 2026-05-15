<?php

namespace App\Http\Controllers\App;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class SaleController extends Controller
{
    public function show(Sale $sale): Response
    {
        $sale->load(['customer', 'seller', 'items.product', 'payments']);

        return Inertia::render('Sales/Show', [
            'sale' => [
                'id' => $sale->id,
                'number' => $sale->number,
                'status' => $sale->status->value,
                'status_label' => $sale->status->label(),
                'subtotal' => (float) $sale->subtotal,
                'tax' => (float) $sale->tax,
                'total' => (float) $sale->total,
                'paid_amount' => (float) $sale->paid_amount,
                'balance' => $sale->balance(),
                'issued_at' => $sale->issued_at?->toIso8601String(),
                'notes' => $sale->notes,
                'customer' => $sale->customer ? [
                    'id' => $sale->customer->id,
                    'name' => $sale->customer->name,
                    'document' => $sale->customer->document_type?->shortLabel().' '.$sale->customer->document_number,
                ] : null,
                'seller' => $sale->seller?->only(['id', 'name']),
                'items' => $sale->items->map(fn ($i) => [
                    'id' => $i->id,
                    'product' => $i->product?->only(['id', 'sku', 'name']),
                    'quantity' => $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                    'line_total' => (float) $i->line_total,
                ])->values(),
                'payments' => $sale->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'method' => $p->method->value,
                    'method_label' => $p->method->label(),
                    'amount' => (float) $p->amount,
                    'paid_at' => $p->paid_at?->toIso8601String(),
                    'reference' => $p->reference,
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', new Enum(PaymentMethod::class)],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = isset($data['customer_id']) ? Customer::find($data['customer_id']) : null;

        try {
            $sale = $sales->confirmAndPay(
                customer: $customer,
                seller: $request->user(),
                items: $data['items'],
                method: PaymentMethod::from($data['payment_method']),
                amountTendered: $data['amount_tendered'] ?? null,
                reference: $data['payment_reference'] ?? null,
                notes: $data['notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect("/sales/{$sale->id}")
            ->with('success', "Venta {$sale->number} registrada correctamente.");
    }
}
