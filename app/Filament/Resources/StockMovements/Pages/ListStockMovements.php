<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Exportar CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $filename = 'movimientos_'.now()->format('Y-m-d_His').'.csv';

                    return response()->streamDownload(function () {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, [
                            'Fecha', 'SKU', 'Producto', 'Tipo', 'Cantidad',
                            'Costo unit.', 'Usuario', 'Notas',
                        ]);
                        StockMovement::query()
                            ->with(['product:id,sku,name', 'user:id,name'])
                            ->orderBy('created_at')
                            ->chunk(500, function ($chunk) use ($handle) {
                                foreach ($chunk as $m) {
                                    fputcsv($handle, [
                                        $m->created_at?->format('Y-m-d H:i'),
                                        $m->product?->sku ?? '',
                                        $m->product?->name ?? '',
                                        $m->type->label(),
                                        $m->quantity,
                                        $m->unit_cost !== null ? number_format((float) $m->unit_cost, 2, '.', '') : '',
                                        $m->user?->name ?? '',
                                        $m->notes ?? '',
                                    ]);
                                }
                            });
                        fclose($handle);
                    }, $filename, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),
        ];
    }
}
