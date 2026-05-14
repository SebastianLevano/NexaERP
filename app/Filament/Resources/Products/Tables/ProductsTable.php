<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->searchable()
                    ->toggleable()
                    ->color('gray'),

                TextColumn::make('sale_price')
                    ->label('Precio')
                    ->money('PEN', locale: 'es_PE')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->alignRight()
                    ->fontFamily('mono')
                    ->badge()
                    ->color(fn ($state, $record) => match (true) {
                        $state <= 0 => 'danger',
                        $state < $record->min_stock => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->numeric()
                    ->toggleable()
                    ->alignRight()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->color('gray')
                    ->size('xs')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),

                TernaryFilter::make('below_minimum')
                    ->label('Stock')
                    ->placeholder('Todos')
                    ->trueLabel('Bajo mínimo')
                    ->falseLabel('Sobre mínimo')
                    ->queries(
                        true: fn ($q) => $q->whereColumn('stock', '<', 'min_stock'),
                        false: fn ($q) => $q->whereColumn('stock', '>=', 'min_stock'),
                    ),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
