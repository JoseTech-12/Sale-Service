<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SalesItem;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'total',
    ];


    public function items(): BelongsTo
    {
        return $this->belongsTo(SalesItem::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
