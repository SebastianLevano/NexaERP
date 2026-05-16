<?php

namespace App\Http\Controllers\App;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Services\InvoicePdfService;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', new Enum(SaleStatus::class)],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $query = Sale::query()
            ->with(['customer:id,name', 'seller:id,name'])
            ->latest('issued_at');

        if ($filters['q'] ?? null) {
            $term = $filters['q'];
            $query->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            });
        }
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['from'] ?? null) {
            $query->whereDate('issued_at', '>=', $filters['from']);
        }
        if ($filters['until'] ?? null) {
            $query->whereDate('issued_at', '<=', $filters['until']);
        }

        $sales = $query->paginate(20)->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => [
                'data' => $sales->getCollection()->map(fn (Sale $s) => [
                    'id' => $s->id,
                    'number' => $s->number,
                    'status' => $s->status->value,
                    'status_label' => $s->status->label(),
                    'total' => (float) $s->total,
                    'paid_amount' => (float) $s->paid_amount,
                    'balance' => $s->balance(),
                    'issued_at' => $s->issued_at?->toIso8601String(),
                    'customer' => $s->customer?->only(['id', 'name']),
                    'seller' => $s->seller?->only(['id', 'name']),
                ])->values(),
                'meta' => [
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                    'per_page' => $sales->perPage(),
                    'total' => $sales->total(),
                    'from' => $sales->firstItem(),
                    'to' => $sales->lastItem(),
                ],
                'links' => [
                    'prev' => $sales->previousPageUrl(),
                    'next' => $sales->nextPageUrl(),
                ],
            ],
            'filters' => $filters,
            'statuses' => collect(SaleStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->values(),
        ]);
    }

    public function show(Request $request, Sale $sale): Response
    {
        $sale->load(['customer', 'seller', 'items.product', 'payments']);

        return Inertia::render('Sales/Show', [
            'sale' => $this->serializeSale($sale),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->values(),
            'canCancel' => $request->user()?->hasRole('Admin') ?? false,
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

    public function cancel(Request $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Admin'), 403);

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $sales->cancel($sale, actor: $request->user(), reason: $data['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Venta {$sale->number} anulada y stock restaurado.");
    }

    public function payment(Request $request, Sale $sale, SaleService $sales): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', new Enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $sales->registerPayment(
                $sale,
                amount: (float) $data['amount'],
                method: PaymentMethod::from($data['method']),
                reference: $data['reference'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', new Enum(SaleStatus::class)],
            'from' => ['nullable', 'date'],
            'until' => ['nullable', 'date'],
        ]);

        $query = Sale::query()->with(['customer:id,name', 'seller:id,name'])->orderBy('issued_at');

        if ($filters['q'] ?? null) {
            $term = $filters['q'];
            $query->where(fn ($q) => $q
                ->where('number', 'like', "%{$term}%")
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%")));
        }
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['from'] ?? null) {
            $query->whereDate('issued_at', '>=', $filters['from']);
        }
        if ($filters['until'] ?? null) {
            $query->whereDate('issued_at', '<=', $filters['until']);
        }

        $filename = 'ventas_'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // BOM para que Excel detecte UTF-8
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Número', 'Fecha', 'Cliente', 'Vendedor', 'Estado',
                'Subtotal', 'IGV', 'Total', 'Pagado', 'Saldo',
            ]);
            $query->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $sale) {
                    fputcsv($handle, [
                        $sale->number,
                        $sale->issued_at?->format('Y-m-d H:i'),
                        $sale->customer?->name ?? '',
                        $sale->seller?->name ?? '',
                        $sale->status->label(),
                        number_format((float) $sale->subtotal, 2, '.', ''),
                        number_format((float) $sale->tax, 2, '.', ''),
                        number_format((float) $sale->total, 2, '.', ''),
                        number_format((float) $sale->paid_amount, 2, '.', ''),
                        number_format($sale->balance(), 2, '.', ''),
                    ]);
                }
            });
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function pdf(Sale $sale, InvoicePdfService $pdf): HttpResponse
    {
        $sale->load(['customer', 'seller', 'items.product', 'payments']);

        return response(
            $pdf->render($sale),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$sale->number.'.pdf"',
            ],
        );
    }

    private function serializeSale(Sale $sale): array
    {
        return [
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
        ];
    }
}
