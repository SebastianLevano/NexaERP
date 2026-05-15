<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleService
{
    public function __construct(private StockService $stock) {}

    /**
     * Create a sale in `draft` status with its line items.
     *
     * @param  array<int, array{product_id:int, quantity:int, unit_price?:float|string}>  $items
     */
    public function createDraft(
        ?Customer $customer,
        ?User $seller,
        array $items,
        ?string $notes = null,
    ): Sale {
        if ($items === []) {
            throw new RuntimeException('La venta debe tener al menos un ítem.');
        }

        return DB::transaction(function () use ($customer, $seller, $items, $notes): Sale {
            $sale = Sale::create([
                'customer_id' => $customer?->id,
                'user_id' => $seller?->id,
                'status' => SaleStatus::Draft,
                'subtotal' => 0,
                'tax' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'notes' => $notes,
            ]);

            foreach ($items as $row) {
                $product = Product::findOrFail($row['product_id']);
                $qty = max(1, (int) $row['quantity']);
                $unitPrice = isset($row['unit_price'])
                    ? (float) $row['unit_price']
                    : (float) $product->sale_price;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => round($unitPrice * $qty, 2),
                ]);
            }

            $this->recalculateTotals($sale);

            return $sale->fresh(['items']);
        });
    }

    /**
     * Confirm a draft sale: deduct stock and lock totals.
     */
    public function confirm(Sale $sale, ?User $actor = null): Sale
    {
        if ($sale->status !== SaleStatus::Draft) {
            throw new RuntimeException("Solo se pueden confirmar ventas en borrador (estado actual: {$sale->status->label()}).");
        }

        return DB::transaction(function () use ($sale, $actor): Sale {
            $sale->loadMissing('items');

            if ($sale->items->isEmpty()) {
                throw new RuntimeException('La venta no tiene ítems.');
            }

            foreach ($sale->items as $item) {
                $this->stock->recordOut(
                    product: $item->product,
                    quantity: $item->quantity,
                    reference: $sale,
                    user: $actor,
                    notes: "Venta {$sale->number}",
                );
            }

            $this->recalculateTotals($sale);
            $sale->status = SaleStatus::Confirmed;
            $sale->save();

            return $sale->fresh(['items']);
        });
    }

    /**
     * Cancel a confirmed/paid sale and reverse stock.
     */
    public function cancel(Sale $sale, ?User $actor = null, ?string $reason = null): Sale
    {
        if ($sale->status === SaleStatus::Cancelled) {
            return $sale;
        }

        if ($sale->status === SaleStatus::Draft) {
            $sale->status = SaleStatus::Cancelled;
            $sale->save();

            return $sale;
        }

        return DB::transaction(function () use ($sale, $actor, $reason): Sale {
            $sale->loadMissing('items');

            foreach ($sale->items as $item) {
                $this->stock->recordIn(
                    product: $item->product,
                    quantity: $item->quantity,
                    user: $actor,
                    notes: 'Reverso por anulación de venta '.$sale->number.($reason ? ': '.$reason : ''),
                );
            }

            $sale->status = SaleStatus::Cancelled;
            $sale->save();

            return $sale->fresh(['items']);
        });
    }

    /**
     * Record a payment against a confirmed/paid sale. Updates paid_amount and
     * promotes status to paid when the balance reaches zero.
     */
    public function registerPayment(
        Sale $sale,
        float $amount,
        PaymentMethod $method,
        ?string $reference = null,
        ?Carbon $paidAt = null,
    ): Payment {
        if ($amount <= 0) {
            throw new RuntimeException('El monto del pago debe ser mayor a cero.');
        }

        if (! in_array($sale->status, [SaleStatus::Confirmed, SaleStatus::Paid], true)) {
            throw new RuntimeException('Solo se pueden registrar pagos sobre ventas confirmadas.');
        }

        return DB::transaction(function () use ($sale, $amount, $method, $reference, $paidAt): Payment {
            $payment = $sale->payments()->create([
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'paid_at' => $paidAt ?? now(),
            ]);

            $sale->paid_amount = round((float) $sale->paid_amount + $amount, 2);
            if ($sale->paid_amount >= (float) $sale->total) {
                $sale->status = SaleStatus::Paid;
            }
            $sale->save();

            return $payment;
        });
    }

    /**
     * POS shortcut: build a sale, confirm it and register a single full
     * payment in one transaction. Returns the persisted Sale.
     *
     * @param  array<int, array{product_id:int, quantity:int, unit_price?:float|string}>  $items
     */
    public function confirmAndPay(
        ?Customer $customer,
        ?User $seller,
        array $items,
        PaymentMethod $method,
        ?float $amountTendered = null,
        ?string $reference = null,
        ?string $notes = null,
    ): Sale {
        return DB::transaction(function () use ($customer, $seller, $items, $method, $amountTendered, $reference, $notes): Sale {
            $sale = $this->createDraft($customer, $seller, $items, $notes);
            $sale = $this->confirm($sale, $seller);

            $amount = $amountTendered ?? (float) $sale->total;
            if ($amount > 0) {
                $this->registerPayment($sale, min($amount, (float) $sale->total), $method, $reference);
            }

            return $sale->fresh(['items', 'payments']);
        });
    }

    private function recalculateTotals(Sale $sale): void
    {
        $sale->loadMissing('items');

        $subtotal = (float) $sale->items->sum('line_total');
        $taxRate = (float) (Setting::get('tax_rate', 0));
        $tax = round($subtotal * $taxRate, 2);
        $total = round($subtotal + $tax, 2);

        $sale->subtotal = $subtotal;
        $sale->tax = $tax;
        $sale->total = $total;
        $sale->save();
    }
}
