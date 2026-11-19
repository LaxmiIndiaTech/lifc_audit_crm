<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanTatRecord extends Model
{
    use HasFactory;

    protected $table = 'loan_tat_records';

    protected $fillable = [
        'loan_account_number',
        'customer_name',
        'mobile_no',
        'product_name',
        'login_date',
        'disbursal_date',
        'sourcing_rm_id',
        'sourcing_rm_name',
        'branch_name',
        'branch_id',
        'state',
        'loan_status',
        'days',
        'branch_average_days',
        'financial_quarter',
        'financial_year',
    ];

    protected $casts = [
        'login_date' => 'date',
        'disbursal_date' => 'date',
        'days' => 'decimal:2',
        'branch_average_days' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
