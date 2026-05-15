<?php

use App\Enums\DocumentType;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('admin can create a customer', function () {
    $this->actingAs(userWithRole('Admin'));

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'María Quispe',
            'document_type' => DocumentType::DNI->value,
            'document_number' => '12345678',
            'email' => 'maria@test.pe',
            'phone' => '987654321',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Customer::where('document_number', '12345678')->exists())->toBeTrue();
});

it('vendedor can also create customers (CRM is shared)', function () {
    $this->actingAs(userWithRole('Vendedor'));

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Empresa SAC',
            'document_type' => DocumentType::RUC->value,
            'document_number' => '20485712634',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Customer::where('document_number', '20485712634')->exists())->toBeTrue();
});

it('blocks Almacén from the customer resource', function () {
    $this->actingAs(userWithRole('Almacén'));

    expect(CustomerResource::canViewAny())->toBeFalse();
});

it('rejects duplicate document_number', function () {
    $this->actingAs(userWithRole('Admin'));
    Customer::factory()->create(['document_number' => '88887777']);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Otro Cliente',
            'document_type' => DocumentType::DNI->value,
            'document_number' => '88887777',
        ])
        ->call('create')
        ->assertHasFormErrors(['document_number']);

    expect(Customer::where('document_number', '88887777')->count())->toBe(1);
});

it('allows blank email and phone (optional fields)', function () {
    $this->actingAs(userWithRole('Admin'));

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Cliente Anonimo',
            'document_type' => DocumentType::DNI->value,
            'document_number' => '11112222',
            'email' => '',
            'phone' => '',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Customer::where('document_number', '11112222')->first();
    expect($created)->not->toBeNull()
        ->and($created->email)->toBeNull()
        ->and($created->phone)->toBeNull();
});

it('list page lists existing customers', function () {
    $this->actingAs(userWithRole('Admin'));
    Customer::factory()->count(3)->create();

    Livewire::test(ListCustomers::class)
        ->assertSuccessful();

    expect(Customer::count())->toBe(3);
});
