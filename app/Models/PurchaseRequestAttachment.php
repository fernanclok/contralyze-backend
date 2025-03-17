<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachment',
        'purchase_request_id',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
