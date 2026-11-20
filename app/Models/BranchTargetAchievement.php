<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchTargetAchievement extends Model
{
    use HasFactory;

    protected $table = 'branch_target_achievements';

    protected $guarded = [];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'portfolio_disbursed_amount' => 'decimal:2',
        'incentive_disbursed_amount' => 'decimal:2',
        'achievement_percentage' => 'decimal:2',
        'total_loans' => 'integer',
        'marks_obtained' => 'decimal:4',
        'carry_marks' => 'decimal:4',
        'meta' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
