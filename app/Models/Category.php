<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'name',
        'type',
        'department_id',
        'company_id'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function budgetRequests()
    {
        return $this->hasMany(BudgetRequest::class);
    }
}
