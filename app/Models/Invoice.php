<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'file_url',
        'type',
        'invoice_number',
        'status',
        'due_date',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    // Relationships
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
