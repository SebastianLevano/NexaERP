<?php

use App\Enums\StockMovementType;
use App\Filament\Pages\StockAdjustment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Admin');

    return $user;
}

function almacenUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Almacén');

    return $user;
}

function vendedorUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('Vendedor');

    return $user;
}

it('admin can perform a positive adjustment and stock increases', function () {
    $admin = adminUser();
    $product = Product::factory()->create(['stock' => 50, 'min_stock' => 10]);

    $this->actingAs($admin);

    Livewire::test(StockAdjustment::class)
        ->set('data.product_id', $product->id)
        ->set('data.quantity', 10)
        ->set('data.notes', 'Inventario físico')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->stock)->toBe(60);

    expect(StockMovement::query()
        ->where('product_id', $product->id)
        ->where('type', StockMovementType::Adjustment)
        ->where('quantity', 10)
        ->exists())->toBeTrue();
});

it('admin cannot drive stock negative; original stock is preserved', function () {
    $admin = adminUser();
    $product = Product::factory()->create(['stock' => 5, 'min_stock' => 0]);

    $this->actingAs($admin);

    Livewire::test(StockAdjustment::class)
        ->set('data.product_id', $product->id)
        ->set('data.quantity', -10)
        ->set('data.notes', 'Prueba')
        ->call('save');

    expect($product->fresh()->stock)->toBe(5);
    expect(StockMovement::query()->where('product_id', $product->id)->count())->toBe(0);
});

it('rejects a zero-quantity adjustment via form validation', function () {
    $admin = adminUser();
    $product = Product::factory()->create(['stock' => 10]);

    $this->actingAs($admin);

    Livewire::test(StockAdjustment::class)
        ->set('data.product_id', $product->id)
        ->set('data.quantity', 0)
        ->set('data.notes', 'Cero')
        ->call('save')
        ->assertHasErrors(['data.quantity']);

    expect($product->fresh()->stock)->toBe(10);
});

it('cached stock equals the sum of stock_movements after a series of operations', function () {
    $admin = adminUser();
    $product = Product::factory()->create(['stock' => 0]);
    $this->actingAs($admin);

    foreach ([20, -5, 8, -3] as $qty) {
        Livewire::test(StockAdjustment::class)
            ->set('data.product_id', $product->id)
            ->set('data.quantity', $qty)
            ->set('data.notes', "ajuste $qty")
            ->call('save');
    }

    $sumMovements = StockMovement::where('product_id', $product->id)->sum('quantity');

    expect($product->fresh()->stock)
        ->toBe(20)
        ->toBe($sumMovements);
});

it('blocks a Vendedor from the Ajuste de Stock page', function () {
    $vendedor = vendedorUser();
    $this->actingAs($vendedor);

    expect(StockAdjustment::canAccess())->toBeFalse();
});

it('allows Almacén role to perform adjustments', function () {
    $almacen = almacenUser();
    $product = Product::factory()->create(['stock' => 20]);

    $this->actingAs($almacen);

    expect(StockAdjustment::canAccess())->toBeTrue();

    Livewire::test(StockAdjustment::class)
        ->set('data.product_id', $product->id)
        ->set('data.quantity', 5)
        ->set('data.notes', 'Reposición')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->stock)->toBe(25);
});
