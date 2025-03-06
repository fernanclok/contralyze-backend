<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItems extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'product_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
