<?php

namespace App\Http\Controllers\App;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class CustomerStoreController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'document_type' => ['required', new Enum(DocumentType::class)],
            'document_number' => ['required', 'string', 'max:32', 'unique:customers,document_number'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'document' => $customer->document_type?->shortLabel().' '.$customer->document_number,
            ],
        ], 201);
    }
}
