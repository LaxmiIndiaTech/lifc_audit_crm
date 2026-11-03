<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'holiday_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Check if a given date is a banking holiday in India:
     * - Every Sunday
     * - 2nd and 4th Saturday of the month (RBI standard rule)
     * - Any date listed in holidays table (National or specific State)
     */
    public static function isBankHoliday(Carbon $date, ?int $stateId = null): bool
    {
        // 1. Every Sunday is closed
        if ($date->isSunday()) {
            return true;
        }

        // 2. 2nd & 4th Saturday is closed (RBI Bank Rule)
        if ($date->isSaturday()) {
            $dayOfMonth = $date->day;
            // 2nd Saturday falls on days 8 through 14
            // 4th Saturday falls on days 22 through 28
            if (($dayOfMonth >= 8 && $dayOfMonth <= 14) || ($dayOfMonth >= 22 && $dayOfMonth <= 28)) {
                return true;
            }
        }

        // 3. Check database holiday master
        $dateStr = $date->toDateString();
        $query = static::active()->where('holiday_date', $dateStr);

        if ($stateId) {
            $query->where(function ($q) use ($stateId) {
                $q->whereNull('state_id')->orWhere('state_id', $stateId);
            });
        } else {
            $query->whereNull('state_id');
        }

        return $query->exists();
    }

    /**
     * Calculate working banking days gap between Created Date and Deposit Date.
     * Excludes:
     * - Sundays
     * - 2nd & 4th Saturdays
     * - Gazetted / Bank Holidays registered in Master
     * 
     * Rule:
     * If deposit is on the same day: gap = 0 (T+0)
     * If deposit is on next working day: gap = 1 (T+1)
     * If deposit is 2 working days later: gap = 2 (T+2)
     */
    public static function calculateWorkingDaysGap($createdDate, $depositDate, ?int $stateId = null): int
    {
        if (empty($createdDate) || empty($depositDate)) {
            return 0;
        }

        $start = Carbon::parse($createdDate)->startOfDay();
        $end = Carbon::parse($depositDate)->startOfDay();

        // If deposited on or before created date
        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        // Fetch all DB holidays in the date range once to optimize performance
        $dbHolidays = static::active()
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->when($stateId, function ($q) use ($stateId) {
                $q->where(function ($sub) use ($stateId) {
                    $sub->whereNull('state_id')->orWhere('state_id', $stateId);
                });
            }, function ($q) {
                $q->whereNull('state_id');
            })
            ->pluck('holiday_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->flip()
            ->all();

        $workingDays = 0;
        $current = $start->copy()->addDay(); // Start counting from day after creation

        while ($current->lessThanOrEqualTo($end)) {
            $isSunday = $current->isSunday();
            $isBankSaturday = false;

            if ($current->isSaturday()) {
                $day = $current->day;
                if (($day >= 8 && $day <= 14) || ($day >= 22 && $day <= 28)) {
                    $isBankSaturday = true;
                }
            }

            $isCustomHoliday = isset($dbHolidays[$current->toDateString()]);

            // Only count if it's an actual open banking working day
            if (!$isSunday && !$isBankSaturday && !$isCustomHoliday) {
                $workingDays++;
            }

            $current->addDay();
        }

        return $workingDays;
    }
}
