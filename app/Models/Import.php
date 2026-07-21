<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One entry in the import history.
 */
class Import extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'replaced_existing' => 'boolean',
        'raw_rows'          => 'integer',
        'crm_rows'          => 'integer',
        'invoice_rows'      => 'integer',
        'students_built'    => 'integer',
        'students_skipped'  => 'integer',
        'file_size'           => 'integer',
        'students_understated' => 'integer',
        'duration_ms'       => 'integer',
    ];

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /** Total data rows read out of the workbook. */
    public function totalRows(): int
    {
        return $this->raw_rows + $this->crm_rows + $this->invoice_rows;
    }

    /** The workbook's own value paste disagrees with its CRM Data. */
    public function hasUnderstated(): bool
    {
        return $this->students_understated > 0;
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /** "2.4 MB" */
    public function humanSize(): string
    {
        $bytes = (float) $this->file_size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 1).' MB';
    }

    /** "1.4s" or "820ms" */
    public function humanDuration(): string
    {
        return $this->duration_ms >= 1000
            ? round($this->duration_ms / 1000, 1).'s'
            : $this->duration_ms.'ms';
    }

    /** @return array<int, string> */
    public function sheets(): array
    {
        return array_filter(explode(',', (string) $this->sheets_found));
    }
}
