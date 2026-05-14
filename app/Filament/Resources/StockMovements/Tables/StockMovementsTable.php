<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray'),

                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('product.name')
                    ->label('Producto')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (StockMovementType $state): string => $state->label())
                    ->color(fn (StockMovementType $state): string => match ($state) {
                        StockMovementType::In => 'success',
                        StockMovementType::Out => 'danger',
                        StockMovementType::Adjustment => 'warning',
                    }),

                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->alignRight()
                    ->fontFamily('mono')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->weight('medium'),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->color('gray')
                    ->size('xs')
                    ->toggleable(),

                TextColumn::make('notes')
                    ->label('Notas')
                    ->color('gray')
                    ->size('xs')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Producto')
                    ->options(fn () => Product::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),

                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(StockMovementType::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all()),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $q, array $data): Builder {
                        return $q
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
