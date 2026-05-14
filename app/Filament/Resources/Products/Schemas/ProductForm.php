<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del producto')
                    ->description('Datos comerciales que verá el equipo de ventas.')
                    ->columns(2)
                    ->components([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->maxLength(40)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'SKU-'.Str::upper(Str::random(6)))
                            ->placeholder('SKU-XXXXXX'),

                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('slug')->required(),
                            ]),

                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Precios y stock')
                    ->columns(2)
                    ->components([
                        TextInput::make('cost_price')
                            ->label('Costo')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->prefix('S/')
                            ->required(),

                        TextInput::make('sale_price')
                            ->label('Precio de venta')
                            ->numeric()
                            ->minValue(0)
                            ->step('0.01')
                            ->prefix('S/')
                            ->required()
                            ->rule(fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                                if ((float) $value < (float) $get('cost_price')) {
                                    $fail('El precio de venta no puede ser menor al costo.');
                                }
                            }),

                        TextInput::make('stock')
                            ->label('Stock actual')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrated(fn ($context) => $context === 'create')
                            ->helperText('Se modifica desde Ajuste de Stock.'),

                        TextInput::make('min_stock')
                            ->label('Stock mínimo')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Se alertará cuando el stock baje de este valor.'),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
