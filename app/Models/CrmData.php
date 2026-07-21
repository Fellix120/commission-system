<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmData extends Model
{
    use HasFactory;

    protected $table = 'crm_data';
    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'fee_total' => 'decimal:2',
        'total_fee_discount' => 'decimal:2',
    ];

    /** Only "Completed" rows count toward commission (Summary tab rule). */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /** Stage 1 = Admission enrolments only. OSHC arrives in stage 2. */
    public function scopeAdmissionOnly($query)
    {
        return $query->where('workflow', 'like', '%Admission%');
    }
}
