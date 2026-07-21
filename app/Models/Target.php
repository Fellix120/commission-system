<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'target_enrolment' => 'integer',
        'target_commission' => 'decimal:2',
    ];
}
