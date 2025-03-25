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
     * Obtener el departamento a través de la categoría
     */
    public function department()
    {
        // La relación correcta es a través de la categoría
        return $this->belongsTo(Department::class)->withDefault(function ($department) {
            return $this->category ? $this->category->department : $department;
        });
    }

    /**
     * Obtener el ID del departamento a través de la categoría
     */
    public function getDepartmentIdAttribute()
    {
        return $this->category ? $this->category->department_id : null;
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
            
        // Asegurar que no devolvemos valores negativos
        return max(0, $this->max_amount - $approvedRequests);
    }

    /**
     * Obtener el presupuesto disponible para un departamento específico.
     */
    public static function getAvailableForDepartment($departmentId, $categoryId = null)
    {
        // Presupuesto total para el departamento
        $query = self::where('status', 'active')
            ->whereHas('category', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
            
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $totalBudget = $query->sum('max_amount');
        
        // Solicitudes aprobadas para el departamento
        $approvedQuery = BudgetRequest::where('status', 'approved')
            ->whereHas('user', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
            
        if ($categoryId) {
            $approvedQuery->where('category_id', $categoryId);
        }
        
        $totalApproved = $approvedQuery->sum('requested_amount');
        
        // Asegurar que no devolvemos valores negativos
        return max(0, $totalBudget - $totalApproved);
    }
}
