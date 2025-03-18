<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'requested_amount',
        'description',
        'request_date',
        'status',
        'reviewed_by'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get all budget requests for a specific department.
     */
    public static function getByDepartment($departmentId, $status = null)
    {
        $query = self::whereHas('user', function($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }
}
