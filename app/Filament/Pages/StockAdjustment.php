<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Services\StockService;
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
use RuntimeException;

class StockAdjustment extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.stock-adjustment';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $title = 'Ajuste de Stock';

    protected static ?string $navigationLabel = 'Ajuste de Stock';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 20;

    /** @var array<string, mixed> */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Almacén']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registrar ajuste manual')
                    ->description('Modifica el stock de un producto creando un movimiento auditado.')
                    ->components([
                        Select::make('product_id')
                            ->label('Producto')
                            ->options(fn () => Product::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Product $p) => [
                                    $p->id => "{$p->name} ({$p->sku}) · stock: {$p->stock}",
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->reactive(),

                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->helperText('Usa positivo (+5) para sumar, negativo (-3) para restar.')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->rule(static function () {
                                return function (string $attribute, $value, $fail): void {
                                    if ((int) $value === 0) {
                                        $fail('La cantidad no puede ser cero.');
                                    }
                                };
                            }),

                        Textarea::make('notes')
                            ->label('Motivo')
                            ->placeholder('Ej: Inventario físico, merma por vencimiento, ingreso por devolución...')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Registrar ajuste')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function save(StockService $stock): void
    {
        $data = $this->form->getState();

        /** @var Product $product */
        $product = Product::findOrFail($data['product_id']);

        try {
            $movement = $stock->adjustment(
                $product,
                (int) $data['quantity'],
                user: auth()->user(),
                notes: $data['notes'],
            );
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('No se pudo registrar el ajuste')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Ajuste registrado')
            ->body("Stock de {$product->fresh()->name}: {$product->fresh()->stock} unidades.")
            ->success()
            ->send();

        $this->form->fill();
    }
}
