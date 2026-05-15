<?php

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SaleService;

beforeEach(function () {
    $this->service = app(SaleService::class);
    $this->seller = User::factory()->create();
    $this->customer = Customer::factory()->create();
});

it('creates a draft sale with items and computes the subtotal', function () {
    $a = Product::factory()->create(['sale_price' => 10.00, 'stock' => 5]);
    $b = Product::factory()->create(['sale_price' => 2.50, 'stock' => 5]);

    $sale = $this->service->createDraft($this->customer, $this->seller, [
        ['product_id' => $a->id, 'quantity' => 2],
        ['product_id' => $b->id, 'quantity' => 4],
    ]);

    expect($sale->status)->toBe(SaleStatus::Draft)
        ->and((float) $sale->subtotal)->toBe(30.00) // 2*10 + 4*2.5
        ->and((float) $sale->total)->toBe(30.00)
        ->and($sale->items)->toHaveCount(2)
        ->and($sale->number)->toStartWith('V-'.now()->year.'-');
});

it('confirms a draft sale and deducts stock per item', function () {
    $product = Product::factory()->create(['sale_price' => 7.00, 'stock' => 10]);
    $sale = $this->service->createDraft($this->customer, $this->seller, [
        ['product_id' => $product->id, 'quantity' => 3],
    ]);

    $confirmed = $this->service->confirm($sale, $this->seller);

    expect($confirmed->status)->toBe(SaleStatus::Confirmed)
        ->and($product->fresh()->stock)->toBe(7);

    $movement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('type', StockMovementType::Out)
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->reference_id)->toBe($sale->id)
        ->and($movement->reference_type)->toBe(Sale::class);
});

it('refuses to confirm when stock is insufficient and rolls everything back', function () {
    $product = Product::factory()->create(['sale_price' => 1.00, 'stock' => 2]);
    $sale = $this->service->createDraft($this->customer, $this->seller, [
        ['product_id' => $product->id, 'quantity' => 5],
    ]);

    expect(fn () => $this->service->confirm($sale, $this->seller))
        ->toThrow(RuntimeException::class, 'Stock insuficiente');

    expect($sale->fresh()->status)->toBe(SaleStatus::Draft)
        ->and($product->fresh()->stock)->toBe(2)
        ->and(StockMovement::count())->toBe(0);
});

it('cancels a confirmed sale and restores stock', function () {
    $product = Product::factory()->create(['sale_price' => 4.00, 'stock' => 10]);
    $sale = $this->service->confirm(
        $this->service->createDraft($this->customer, $this->seller, [
            ['product_id' => $product->id, 'quantity' => 4],
        ]),
        $this->seller,
    );

    expect($product->fresh()->stock)->toBe(6);

    $cancelled = $this->service->cancel($sale, $this->seller, 'cliente desistió');

    expect($cancelled->status)->toBe(SaleStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(10); // reversed
});

it('records a partial payment without flipping to paid', function () {
    $product = Product::factory()->create(['sale_price' => 50.00, 'stock' => 1]);
    $sale = $this->service->confirm(
        $this->service->createDraft($this->customer, $this->seller, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]),
        $this->seller,
    );

    $this->service->registerPayment($sale, 20.00, PaymentMethod::Cash);

    expect($sale->fresh()->status)->toBe(SaleStatus::Confirmed)
        ->and((float) $sale->fresh()->paid_amount)->toBe(20.00)
        ->and($sale->fresh()->balance())->toBe(30.00);
});

it('promotes to paid once total is fully covered', function () {
    $product = Product::factory()->create(['sale_price' => 50.00, 'stock' => 1]);
    $sale = $this->service->confirm(
        $this->service->createDraft($this->customer, $this->seller, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]),
        $this->seller,
    );

    $this->service->registerPayment($sale, 20.00, PaymentMethod::Cash);
    $this->service->registerPayment($sale, 30.00, PaymentMethod::Transfer, 'OP-887');

    expect($sale->fresh()->status)->toBe(SaleStatus::Paid)
        ->and($sale->fresh()->isFullyPaid())->toBeTrue()
        ->and($sale->fresh()->payments)->toHaveCount(2);
});

it('issues sale numbers in a strict V-YYYY-NNNNN sequence', function () {
    $product = Product::factory()->create(['sale_price' => 1.00, 'stock' => 99]);

    $first = $this->service->createDraft($this->customer, $this->seller, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);
    $second = $this->service->createDraft($this->customer, $this->seller, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($first->number)->toBe('V-'.now()->year.'-00001')
        ->and($second->number)->toBe('V-'.now()->year.'-00002');
});

it('cannot confirm the same sale twice', function () {
    $product = Product::factory()->create(['sale_price' => 1.00, 'stock' => 10]);
    $sale = $this->service->confirm(
        $this->service->createDraft($this->customer, $this->seller, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]),
        $this->seller,
    );

    expect(fn () => $this->service->confirm($sale, $this->seller))
        ->toThrow(RuntimeException::class, 'Solo se pueden confirmar ventas en borrador');
});

it('confirmAndPay creates a paid sale in a single call (POS shortcut)', function () {
    $product = Product::factory()->create(['sale_price' => 12.50, 'stock' => 5]);

    $sale = $this->service->confirmAndPay(
        customer: $this->customer,
        seller: $this->seller,
        items: [['product_id' => $product->id, 'quantity' => 2]],
        method: PaymentMethod::Cash,
    );

    expect($sale->status)->toBe(SaleStatus::Paid)
        ->and((float) $sale->total)->toBe(25.00)
        ->and((float) $sale->paid_amount)->toBe(25.00)
        ->and($product->fresh()->stock)->toBe(3)
        ->and($sale->payments)->toHaveCount(1);
});
