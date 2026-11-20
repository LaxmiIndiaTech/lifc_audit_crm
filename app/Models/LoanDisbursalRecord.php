<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanDisbursalRecord extends Model
{
    use HasFactory;

    protected $table = 'loan_disbursal_records';

    protected $guarded = [];

    protected $casts = [
        'disbursal_amount' => 'decimal:2',
        'disbursal_date' => 'date',
        'meta' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
