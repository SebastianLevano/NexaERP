<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nombre / Razón social')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),

                        Select::make('document_type')
                            ->label('Tipo de documento')
                            ->options(collect(DocumentType::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                                ->all())
                            ->default(DocumentType::DNI->value)
                            ->required()
                            ->native(false),

                        TextInput::make('document_number')
                            ->label('Número de documento')
                            ->required()
                            ->maxLength(32)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ej: 12345678 (DNI) · 20123456789 (RUC)'),
                    ]),

                Section::make('Contacto')
                    ->columns(2)
                    ->components([
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->maxLength(120),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(32),

                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Notas internas')
                    ->collapsed()
                    ->components([
                        Textarea::make('notes')
                            ->label(false)
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }
}
