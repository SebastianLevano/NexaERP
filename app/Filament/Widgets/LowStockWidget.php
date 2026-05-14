<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    protected static ?string $heading = 'Productos bajo mínimo';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Almacén']) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn (): Builder => Product::query()
                    ->with('category')
                    ->where('is_active', true)
                    ->whereColumn('stock', '<', 'min_stock')
                    ->orderByRaw('(stock - min_stock) ASC'),
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Todo bajo control')
            ->emptyStateDescription('Ningún producto está por debajo de su stock mínimo.')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Producto')
                    ->weight('medium')
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->alignRight()
                    ->fontFamily('mono')
                    ->badge()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'warning'),

                TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->alignRight()
                    ->fontFamily('mono')
                    ->color('gray'),

                TextColumn::make('deficit')
                    ->label('Déficit')
                    ->alignRight()
                    ->fontFamily('mono')
                    ->state(fn (Product $product) => $product->min_stock - $product->stock)
                    ->color('danger')
                    ->formatStateUsing(fn ($state) => '−'.$state),
            ]);
    }
}
