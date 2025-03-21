<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_uid',
        'title',
        'total_amount',
        'justification',
        'request_date',
        'priority',
        'status',
        'items',
        'rejection_reason', 
        'user_id',
        'department_id',
        'supplier_id',
        'client_id',
        'reviewed_by',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function attachments()
    {
        return $this->hasMany(PurchaseRequestAttachment::class);
    }
}
