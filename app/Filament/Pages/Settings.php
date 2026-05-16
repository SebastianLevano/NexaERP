<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $title = 'Ajustes';

    protected static ?string $navigationLabel = 'Ajustes';

    protected static ?int $navigationSort = 100;

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Admin') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'company_name' => Setting::get('company_name', config('app.name')),
            'company_document' => Setting::get('company_document'),
            'company_address' => Setting::get('company_address'),
            'company_phone' => Setting::get('company_phone'),
            'company_email' => Setting::get('company_email'),
            'invoice_footer' => Setting::get('invoice_footer'),
            'tax_rate' => (float) Setting::get('tax_rate', 0),
            'currency_code' => Setting::get('currency_code', 'PEN'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la empresa')
                    ->description('Aparecen en el comprobante PDF y reportes.')
                    ->columns(2)
                    ->components([
                        TextInput::make('company_name')
                            ->label('Razón social')
                            ->required()
                            ->maxLength(180)
                            ->columnSpanFull(),
                        TextInput::make('company_document')
                            ->label('RUC')
                            ->maxLength(20),
                        TextInput::make('company_phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(32),
                        TextInput::make('company_address')
                            ->label('Dirección fiscal')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('company_email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(120)
                            ->columnSpanFull(),
                    ]),

                Section::make('Configuración fiscal y moneda')
                    ->columns(2)
                    ->components([
                        TextInput::make('tax_rate')
                            ->label('% de IGV (decimal)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step('0.01')
                            ->helperText('Ej: 0.18 para 18%. Dejar en 0 si las facturas son sin IGV.'),
                        Select::make('currency_code')
                            ->label('Moneda')
                            ->options([
                                'PEN' => 'Soles (PEN)',
                                'USD' => 'Dólares (USD)',
                                'EUR' => 'Euros (EUR)',
                            ])
                            ->default('PEN')
                            ->native(false),
                    ]),

                Section::make('Pie de comprobante')
                    ->collapsed()
                    ->components([
                        Textarea::make('invoice_footer')
                            ->label(false)
                            ->rows(2)
                            ->maxLength(255)
                            ->placeholder('Ej: Gracias por su compra · Síganos en redes...'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Guardar cambios')->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        Notification::make()
            ->title('Ajustes guardados')
            ->success()
            ->send();
    }
}
