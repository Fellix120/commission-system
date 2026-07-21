<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentIntakeCommission extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['amount_received' => 'decimal:2'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
