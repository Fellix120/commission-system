<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'course_duration' => 'decimal:6',
        'fee_total' => 'decimal:2',
        'credit_fee' => 'decimal:2',
        'bonus_due' => 'decimal:2',
        'paid_fee' => 'decimal:2',
        'paid_bonus' => 'decimal:2',
        'remaining_fee' => 'decimal:2',
        'remaining_bonus' => 'decimal:2',
        'fee_adjustment' => 'decimal:2',
        'bonus_adjustment' => 'decimal:2',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'values_refreshed_at' => 'datetime',
    ];

    /**
     * The stored Product Name lists more courses than the CRM rows behind it,
     * so Fee Total is understated.
     *
     * Product Name is a TEXTJOIN of every course for the client, and Fee Total
     * is a SUMIFS over the same rows. If the name says "Diploma + Bachelor" but
     * only one CRM row exists, the export is missing the other course and the
     * fee is short. This is the drift found in the sample workbook for clients
     * such as 12630942.
     */
    public function feeLooksUnderstated(int $crmRowCount): bool
    {
        if ($crmRowCount < 1) {
            return false;
        }

        $coursesNamed = substr_count((string) $this->product_name, ' + ') + 1;

        return $coursesNamed > $crmRowCount;
    }

    public function intakeCommissions()
    {
        return $this->hasMany(StudentIntakeCommission::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'sid', 'client_id_invoice');
    }

    public function crmRows()
    {
        return $this->hasMany(CrmData::class, 'client_id', 'client_id_crm');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /** Requirement 3: only settle-and-archive when nothing is outstanding. */
    public function isSettled(): bool
    {
        return (float) $this->remaining_fee <= 0.01
            && (float) $this->remaining_bonus <= 0.01;
    }

    public function archive(?string $note = null, ?string $by = null): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $by ?? 'system',
            'archive_note' => $note,
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
            'archive_note' => null,
        ]);
    }

    /** Convenience: amount received for a given intake code. */
    public function receivedFor(string $intakeCode): float
    {
        return (float) ($this->intakeCommissions
            ->firstWhere('intake_code', $intakeCode)?->amount_received ?? 0);
    }
}
