<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(StockService $stock): void
    {
        $admin = User::where('email', 'admin@nexaerp.test')->first();

        $catalog = [
            'Bebidas' => [
                ['name' => 'Coca-Cola 500ml', 'cost' => 2.20, 'sale' => 3.50, 'min' => 12, 'stock' => 48],
                ['name' => 'Inca Kola 1.5L', 'cost' => 4.80, 'sale' => 7.50, 'min' => 6, 'stock' => 24],
                ['name' => 'Agua San Luis 625ml', 'cost' => 1.10, 'sale' => 2.00, 'min' => 24, 'stock' => 60],
                ['name' => 'Cerveza Pilsen 355ml', 'cost' => 3.50, 'sale' => 5.50, 'min' => 24, 'stock' => 8],
            ],
            'Snacks' => [
                ['name' => 'Lay\'s Clásicas 45g', 'cost' => 1.80, 'sale' => 3.00, 'min' => 20, 'stock' => 32],
                ['name' => 'Doritos Nacho 80g', 'cost' => 2.50, 'sale' => 4.00, 'min' => 15, 'stock' => 18],
                ['name' => 'Sublime Clásico', 'cost' => 1.20, 'sale' => 2.00, 'min' => 30, 'stock' => 45],
                ['name' => 'Galletas Casino Fresa', 'cost' => 0.90, 'sale' => 1.50, 'min' => 40, 'stock' => 12],
            ],
            'Abarrotes' => [
                ['name' => 'Arroz Costeño 5kg', 'cost' => 18.00, 'sale' => 24.50, 'min' => 5, 'stock' => 14],
                ['name' => 'Aceite Primor 1L', 'cost' => 7.50, 'sale' => 11.00, 'min' => 8, 'stock' => 22],
                ['name' => 'Azúcar Rubia 1kg', 'cost' => 3.40, 'sale' => 4.80, 'min' => 10, 'stock' => 0],
                ['name' => 'Fideos Don Vittorio 500g', 'cost' => 2.10, 'sale' => 3.50, 'min' => 15, 'stock' => 28],
            ],
            'Limpieza' => [
                ['name' => 'Detergente Bolívar 360g', 'cost' => 4.50, 'sale' => 7.00, 'min' => 10, 'stock' => 6],
                ['name' => 'Lejía Clorox 1L', 'cost' => 5.20, 'sale' => 8.00, 'min' => 8, 'stock' => 18],
                ['name' => 'Papel Higiénico Suave x4', 'cost' => 4.00, 'sale' => 6.50, 'min' => 20, 'stock' => 35],
            ],
        ];

        foreach ($catalog as $categoryName => $items) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName],
            );

            foreach ($items as $item) {
                $product = Product::firstOrCreate(
                    ['name' => $item['name']],
                    [
                        'sku' => 'SKU-'.Str::upper(Str::random(6)),
                        'category_id' => $category->id,
                        'cost_price' => $item['cost'],
                        'sale_price' => $item['sale'],
                        'stock' => 0,
                        'min_stock' => $item['min'],
                        'is_active' => true,
                    ],
                );

                if ($product->stock === 0 && $item['stock'] > 0) {
                    $stock->recordIn(
                        $product,
                        $item['stock'],
                        user: $admin,
                        notes: 'Stock inicial (datos demo).',
                        unitCost: $item['cost'],
                    );
                }
            }
        }

        $this->seedCustomers();
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['Distribuidora San Marcos S.A.C.', DocumentType::RUC, '20485712633', 'ventas@sanmarcos.pe', '014257821', 'Av. Argentina 1234, Lima'],
            ['Bodega El Águila E.I.R.L.', DocumentType::RUC, '20512987441', 'contacto@elaguila.pe', '986123456', 'Jr. Lampa 567, Lima'],
            ['Inversiones Linder S.A.C.', DocumentType::RUC, '20603411887', null, null, 'Av. La Molina 890'],
            ['María Fernanda Quispe Vargas', DocumentType::DNI, '47128635', 'mfquispe@gmail.com', '987654321', 'Calle Los Olivos 234, San Borja'],
            ['Carlos Augusto Ramos Tello', DocumentType::DNI, '08912447', 'cramos@outlook.com', '999123654', null],
            ['Jhonatan Pérez Cárdenas', DocumentType::DNI, '72445981', null, '942887135', 'Av. Brasil 1450'],
            ['Lucía Mendoza Núñez', DocumentType::DNI, '45129883', 'lucia.mendoza@gmail.com', null, 'Calle Las Begonias 88, Surco'],
            ['Steven J. Walker', DocumentType::Passport, 'P5847291', 'swalker@example.com', null, 'Hotel Sheraton 4502, Miraflores'],
        ];

        foreach ($customers as [$name, $type, $doc, $email, $phone, $address]) {
            Customer::firstOrCreate(
                ['document_number' => $doc],
                [
                    'name' => $name,
                    'document_type' => $type,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                ],
            );
        }
    }
}
