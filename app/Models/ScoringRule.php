<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoringRule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'min_days' => 'integer',
        'max_days' => 'integer',
        'score' => 'integer',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'meta' => 'array',
    ];

    public function scopeActiveRules($query)
    {
        return $query->where('is_deleted', false)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeCashCheque($query)
    {
        return $query->where('rule_type', 'cash_cheque');
    }

    public function scopeTat($query)
    {
        return $query->where('rule_type', 'tat_disbursement');
    }

    public static function checkOverlap(int $minDays, int $maxDays, ?int $ignoreId = null, string $ruleType = 'cash_cheque'): bool
    {
        $query = static::active()
            ->where('rule_type', $ruleType)
            ->where(function ($q) use ($minDays, $maxDays) {
                // NOT (max_days < minDays OR min_days > maxDays)
                $q->where('max_days', '>=', $minDays)
                  ->where('min_days', '<=', $maxDays);
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function receipts()
    {
        return $this->hasMany(CashChequeReceipt::class, 'scoring_rules_id');
    }
}
