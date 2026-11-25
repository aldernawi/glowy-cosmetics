<?php

namespace App\Models;

use App\Enums\PosPaymentMethod;
use App\Enums\PosSaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSale extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PosSaleStatus::class,
            'payment_method' => PosPaymentMethod::class,
            'sequence' => 'integer',
            'total' => 'decimal:3',
            'amount_received' => 'decimal:3',
            'change_due' => 'decimal:3',
            'sold_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'pos_device_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
