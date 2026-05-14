<?php

namespace App\Filament\Resources\StockMovements;

use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Movimientos de stock';

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos de stock';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Almacén']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
        ];
    }
}
