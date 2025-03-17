<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'max_amount',
        'user_id',
        'start_date',
        'end_date',
        'status'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcula el presupuesto disponible basado en solicitudes aprobadas.
     */
    public function getAvailableAmount()
    {
        // Obtener todas las solicitudes aprobadas para esta categoría
        $approvedRequests = BudgetRequest::where('status', 'approved')
            ->where('category_id', $this->category_id)
            ->sum('requested_amount');
            
        return $this->max_amount - $approvedRequests;
    }

    /**
     * Obtener el presupuesto disponible para un departamento específico.
     */
    public static function getAvailableForDepartment($departmentId, $categoryId = null)
    {
        // Obtener usuarios del departamento
        $departmentUsers = User::where('department_id', $departmentId)->pluck('id');
        
        // Presupuesto total para el departamento
        $query = self::whereIn('user_id', $departmentUsers)
            ->where('status', 'active');
            
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $totalBudget = $query->sum('max_amount');
        
        // Solicitudes aprobadas para el departamento
        $approvedQuery = BudgetRequest::whereIn('user_id', $departmentUsers)
            ->where('status', 'approved');
            
        if ($categoryId) {
            $approvedQuery->where('category_id', $categoryId);
        }
        
        $totalApproved = $approvedQuery->sum('requested_amount');
        
        return $totalBudget - $totalApproved;
    }
}
