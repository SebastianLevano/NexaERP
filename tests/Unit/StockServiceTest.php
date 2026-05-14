<?php

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;

beforeEach(function () {
    $this->service = app(StockService::class);
});

it('records an IN movement and increases stock', function () {
    $product = Product::factory()->create(['stock' => 0]);

    $movement = $this->service->recordIn($product, 25);

    expect($product->fresh()->stock)->toBe(25)
        ->and($movement->type)->toBe(StockMovementType::In)
        ->and($movement->quantity)->toBe(25);
});

it('records an OUT movement and decreases stock', function () {
    $product = Product::factory()->create(['stock' => 50]);

    $this->service->recordOut($product, 18);

    expect($product->fresh()->stock)->toBe(32);
});

it('throws when an OUT would leave negative stock', function () {
    $product = Product::factory()->create(['stock' => 5]);

    expect(fn () => $this->service->recordOut($product, 10))
        ->toThrow(RuntimeException::class, 'Stock insuficiente');

    expect($product->fresh()->stock)->toBe(5);
});

it('supports signed adjustments (positive or negative)', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->service->adjustment($product, +7);
    expect($product->fresh()->stock)->toBe(17);

    $this->service->adjustment($product, -4);
    expect($product->fresh()->stock)->toBe(13);
});

it('rejects zero-quantity movements', function () {
    $product = Product::factory()->create(['stock' => 10]);

    expect(fn () => $this->service->recordIn($product, 0))
        ->toThrow(RuntimeException::class, 'no puede ser cero');
});

it('attaches the user that triggered the movement', function () {
    $product = Product::factory()->create(['stock' => 0]);
    $user = User::factory()->create();

    $movement = $this->service->recordIn($product, 5, user: $user, notes: 'Compra inicial');

    expect($movement->user_id)->toBe($user->id)
        ->and($movement->notes)->toBe('Compra inicial');
});
