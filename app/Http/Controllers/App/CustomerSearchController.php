<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('document_number', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'document_type', 'document_number'])
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'document' => $c->document_type?->shortLabel().' '.$c->document_number,
            ]);

        return response()->json(['data' => $customers]);
    }
}
