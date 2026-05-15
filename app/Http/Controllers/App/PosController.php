<?php

namespace App\Http\Controllers\App;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Sales/Pos', [
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ])->values(),
        ]);
    }
}
