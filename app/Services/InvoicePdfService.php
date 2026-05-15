<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function render(Sale $sale): string
    {
        $sale->loadMissing(['customer', 'seller', 'items.product', 'payments']);

        $company = [
            'name' => Setting::get('company_name', config('app.name')),
            'document' => Setting::get('company_document'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'email' => Setting::get('company_email'),
        ];

        return Pdf::loadView('pdf.invoice', [
            'sale' => $sale,
            'company' => $company,
        ])
            ->setPaper('a5', 'portrait')
            ->output();
    }
}
