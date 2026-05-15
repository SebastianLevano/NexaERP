<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\DocumentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre / Razón social')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('document_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (DocumentType $state) => $state->shortLabel())
                    ->color(fn (DocumentType $state) => match ($state) {
                        DocumentType::DNI => 'gray',
                        DocumentType::RUC => 'info',
                        DocumentType::CE => 'warning',
                        DocumentType::Passport => 'success',
                    }),

                TextColumn::make('document_number')
                    ->label('Documento')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->color('gray')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->fontFamily('mono')
                    ->size('xs')
                    ->color('gray')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->color('gray')
                    ->size('xs')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Tipo de documento')
                    ->options(collect(DocumentType::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all()),
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
