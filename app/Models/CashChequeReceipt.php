<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashChequeReceipt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_date' => 'date',
        'payment_date' => 'datetime',
        'deposit_date' => 'date',
        'lms_req_id_updated_at' => 'datetime',
        'emi_amount_paid' => 'decimal:2',
        'late_payment_charges_paid' => 'decimal:2',
        'bounce_charges_paid' => 'decimal:2',
        'penalty_charges_paid' => 'decimal:2',
        'other_charges_paid' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'charges' => 'decimal:2',
        'gap_in_days' => 'integer',
        'is_over_tat' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($receipt) {
            $receipt->assignScoringRule();
        });
    }

    public function assignScoringRule(): void
    {
        $baseDate = !empty($this->created_date) ? $this->created_date : $this->payment_date;
        if (empty($this->deposit_date) || empty($baseDate)) {
            return;
        }

        try {
            // Calculate gap in working banking days (excluding Sundays, 2nd/4th Saturdays & holidays)
            $gapDays = Holiday::calculateWorkingDaysGap($baseDate, $this->deposit_date, $this->state_id);

            $this->gap_in_days = $gapDays;
            $this->tat_status = 'T+' . $gapDays;
            $this->is_over_tat = ($gapDays > 1);

            $rule = ScoringRule::activeRules()
                ->where('min_days', '<=', $gapDays)
                ->where('max_days', '>=', $gapDays)
                ->first();

            if ($rule) {
                $this->scoring_rules_id = $rule->id;
            }
        } catch (\Throwable $e) {
            // Ignore date parsing failures
        }
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function scoringRule()
    {
        return $this->belongsTo(ScoringRule::class, 'scoring_rules_id');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByMonth($query, $date)
    {
        $d = Carbon::parse($date);
        return $query->whereBetween('payment_date', [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()]);
    }
}
