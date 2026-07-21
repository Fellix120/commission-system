<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawDataExport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'added_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_updated' => 'date',
        'fee_total' => 'decimal:2',
    ];
}
