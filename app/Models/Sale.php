<?php

namespace App\Models;

use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'number',
        'customer_id',
        'user_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'paid_amount',
        'notes',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale): void {
            if (blank($sale->number)) {
                $sale->number = static::generateNumber();
            }
            if (blank($sale->issued_at)) {
                $sale->issued_at = now();
            }
        });
    }

    public static function generateNumber(?Carbon $when = null): string
    {
        $year = ($when ?? now())->year;
        $prefix = "V-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $lastNumber = static::query()
                ->where('number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('number');

            $nextSeq = $lastNumber
                ? ((int) substr($lastNumber, strlen($prefix))) + 1
                : 1;

            return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->paid_amount, 2);
    }

    public function isFullyPaid(): bool
    {
        return $this->balance() <= 0;
    }
}
