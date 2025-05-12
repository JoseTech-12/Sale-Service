<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Sale;

class SalesItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];


    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
