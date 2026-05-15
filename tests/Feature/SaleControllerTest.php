<?php

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function userAs(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function makePaidSale(int $stock = 10, int $qty = 2, float $price = 25): Sale
{
    $product = Product::factory()->create(['sale_price' => $price, 'stock' => $stock]);
    $customer = Customer::factory()->create();

    return app(SaleService::class)->confirmAndPay(
        customer: $customer,
        seller: User::factory()->create(),
        items: [['product_id' => $product->id, 'quantity' => $qty]],
        method: PaymentMethod::Cash,
    );
}

it('vendedor sees the sales index', function () {
    $this->actingAs(userAs('Vendedor'));
    makePaidSale();

    $this->get('/sales')->assertOk();
});

it('almacén is forbidden from /sales', function () {
    $this->actingAs(userAs('Almacén'));

    $this->get('/sales')->assertForbidden();
});

it('filters sales by status', function () {
    $this->actingAs(userAs('Admin'));
    makePaidSale();

    $this->get('/sales?status=paid')->assertOk();
    $this->get('/sales?status=cancelled')->assertOk();
});

it('admin can cancel a paid sale and stock is restored', function () {
    $admin = userAs('Admin');
    $this->actingAs($admin);

    $product = Product::factory()->create(['sale_price' => 20, 'stock' => 10]);
    $sale = app(SaleService::class)->confirmAndPay(
        customer: Customer::factory()->create(),
        seller: $admin,
        items: [['product_id' => $product->id, 'quantity' => 3]],
        method: PaymentMethod::Cash,
    );

    expect($product->fresh()->stock)->toBe(7);

    $this->post("/sales/{$sale->id}/cancel", ['reason' => 'cliente desistió'])
        ->assertRedirect();

    expect($sale->fresh()->status)->toBe(SaleStatus::Cancelled)
        ->and($product->fresh()->stock)->toBe(10);
});

it('vendedor cannot cancel a sale (403)', function () {
    $vendedor = userAs('Vendedor');
    $sale = makePaidSale();

    $this->actingAs($vendedor)
        ->post("/sales/{$sale->id}/cancel", ['reason' => 'test'])
        ->assertForbidden();

    expect($sale->fresh()->status)->toBe(SaleStatus::Paid);
});

it('registers a partial payment on a confirmed sale and stays confirmed', function () {
    $admin = userAs('Admin');
    $this->actingAs($admin);

    $product = Product::factory()->create(['sale_price' => 100, 'stock' => 5]);
    $sale = app(SaleService::class)->createDraft(
        customer: Customer::factory()->create(),
        seller: $admin,
        items: [['product_id' => $product->id, 'quantity' => 1]],
    );
    app(SaleService::class)->confirm($sale, $admin);

    $this->post("/sales/{$sale->id}/payments", [
        'amount' => 40,
        'method' => PaymentMethod::Cash->value,
    ])->assertRedirect();

    $fresh = $sale->fresh();
    expect((float) $fresh->paid_amount)->toBe(40.00)
        ->and($fresh->status)->toBe(SaleStatus::Confirmed);
});

it('promotes to paid when the balance reaches zero', function () {
    $admin = userAs('Admin');
    $this->actingAs($admin);

    $product = Product::factory()->create(['sale_price' => 50, 'stock' => 5]);
    $sale = app(SaleService::class)->createDraft(
        customer: Customer::factory()->create(),
        seller: $admin,
        items: [['product_id' => $product->id, 'quantity' => 1]],
    );
    app(SaleService::class)->confirm($sale, $admin);

    $this->post("/sales/{$sale->id}/payments", [
        'amount' => 50,
        'method' => PaymentMethod::Transfer->value,
        'reference' => 'OP-123',
    ])->assertRedirect();

    expect($sale->fresh()->status)->toBe(SaleStatus::Paid);
});

it('downloads a PDF for a sale', function () {
    $this->actingAs(userAs('Admin'));
    $sale = makePaidSale();

    $response = $this->get("/sales/{$sale->id}/pdf");
    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(strlen($response->getContent()))->toBeGreaterThan(1000);
});

it('show page returns payload with canCancel flag based on role', function () {
    $admin = userAs('Admin');
    $sale = makePaidSale();

    $this->actingAs($admin)
        ->get("/sales/{$sale->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/Show')
            ->where('canCancel', true)
            ->has('sale.items'));

    $vendedor = userAs('Vendedor');
    $this->actingAs($vendedor)
        ->get("/sales/{$sale->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCancel', false));
});
