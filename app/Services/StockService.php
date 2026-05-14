<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * Record a stock movement and recalculate the product's cached stock.
     * `quantity` is always passed as a positive integer; the sign is derived
     * from the movement type (In/Adjustment: +qty, Out: -qty).
     * For Adjustment, callers may pass a negative `signedQuantity` directly
     * via `adjustment()`.
     */
    public function movement(
        Product $product,
        StockMovementType $type,
        int $quantity,
        ?Model $reference = null,
        ?User $user = null,
        ?string $notes = null,
        ?float $unitCost = null,
    ): StockMovement {
        if ($quantity === 0) {
            throw new RuntimeException('La cantidad del movimiento no puede ser cero.');
        }

        $signed = match ($type) {
            StockMovementType::In => abs($quantity),
            StockMovementType::Out => -abs($quantity),
            StockMovementType::Adjustment => $quantity,
        };

        return DB::transaction(function () use ($product, $type, $signed, $reference, $user, $notes, $unitCost) {
            /** @var Product $locked */
            $locked = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $newStock = $locked->stock + $signed;

            if ($newStock < 0) {
                throw new RuntimeException(
                    "Stock insuficiente para {$locked->name}. Disponible: {$locked->stock}, requerido: " . abs($signed) . '.',
                );
            }

            $movement = new StockMovement([
                'product_id' => $locked->id,
                'type' => $type->value,
                'quantity' => $signed,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'unit_cost' => $unitCost,
                'notes' => $notes,
                'user_id' => $user?->id,
            ]);
            $movement->save();

            $locked->stock = $newStock;
            $locked->save();

            $product->setRawAttributes($locked->getAttributes(), true);

            return $movement;
        });
    }

    public function recordIn(Product $product, int $quantity, ?User $user = null, ?string $notes = null, ?float $unitCost = null): StockMovement
    {
        return $this->movement($product, StockMovementType::In, $quantity, user: $user, notes: $notes, unitCost: $unitCost);
    }

    public function recordOut(Product $product, int $quantity, ?Model $reference = null, ?User $user = null, ?string $notes = null): StockMovement
    {
        return $this->movement($product, StockMovementType::Out, $quantity, reference: $reference, user: $user, notes: $notes);
    }

    public function adjustment(Product $product, int $signedQuantity, ?User $user = null, ?string $notes = null): StockMovement
    {
        return $this->movement($product, StockMovementType::Adjustment, $signedQuantity, user: $user, notes: $notes);
    }
}
