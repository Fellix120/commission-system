<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_fee' => 'decimal:2',
        'rate' => 'decimal:4',
        'commission' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_commission' => 'decimal:2',
        'royalty_rate' => 'decimal:4',
        'bonus' => 'decimal:2',
        'eevs_ho' => 'decimal:2',
        'branch_commission' => 'decimal:2',
        'branch_bonus' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'sid', 'client_id_invoice');
    }
}
