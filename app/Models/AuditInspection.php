<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditInspection extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'inspection_date' => 'date',
        'process_marks_carry' => 'decimal:4',
        'process_marks_obtained' => 'decimal:4',
        'process_score_pct' => 'decimal:4',
        'performance_marks_carry' => 'decimal:4',
        'performance_marks_obtained' => 'decimal:4',
        'performance_score_pct' => 'decimal:4',
        'total_marks_carry' => 'decimal:4',
        'total_marks_obtained' => 'decimal:4',
        'total_score_pct' => 'decimal:4',
        'meta' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scores()
    {
        return $this->hasMany(InspectionCheckpointScore::class);
    }

    public function feedbackThreads()
    {
        return $this->hasMany(FeedbackThread::class);
    }

    public function recalculateTotals(): void
    {
        // Calculate Section I: Process (Sections A, B, C, D, E, F, G)
        $processScores = $this->scores()
            ->whereIn('section', ['A', 'B', 'C', 'D', 'E', 'F', 'G'])
            ->get();

        $processCarry = $processScores->sum('carry_marks') ?: 40.0;
        $processObtained = $processScores->sum('marks_obtained');
        $processPct = $processCarry > 0 ? round(($processObtained / $processCarry) * 100, 4) : 0;

        // Calculate Section II: Performance (Sections H, I, J, K, L, M)
        $perfScores = $this->scores()
            ->whereIn('section', ['H', 'I', 'J', 'K', 'L', 'M'])
            ->get();

        $perfCarry = $perfScores->sum('carry_marks') ?: 60.0;
        $perfObtained = $perfScores->sum('marks_obtained');
        $perfPct = $perfCarry > 0 ? round(($perfObtained / $perfCarry) * 100, 4) : 0;

        // Total 100
        $totalCarry = $processCarry + $perfCarry;
        $totalObtained = $processObtained + $perfObtained;
        $totalPct = $totalCarry > 0 ? round(($totalObtained / $totalCarry) * 100, 4) : 0;

        // If Performance section hasn't been filled yet, grade by Process score; otherwise overall score
        $gradeBasePct = ($perfScores->count() > 0) ? $totalPct : $processPct;
        $grade = static::calculateGrade($gradeBasePct);

        $this->update([
            'process_marks_carry' => $processCarry,
            'process_marks_obtained' => round($processObtained, 4),
            'process_score_pct' => $processPct,
            'performance_marks_carry' => $perfCarry,
            'performance_marks_obtained' => round($perfObtained, 4),
            'performance_score_pct' => $perfPct,
            'total_marks_carry' => $totalCarry,
            'total_marks_obtained' => round($totalObtained, 4),
            'total_score_pct' => $totalPct,
            'final_grade' => $grade,
        ]);
    }

    public static function calculateGrade(float $percentage): string
    {
        if ($percentage > 90) {
            return 'Excellent';
        } elseif ($percentage > 65) {
            return 'Good';
        } elseif ($percentage > 50) {
            return 'Satisfactory';
        } else {
            return 'Unsatisfactory';
        }
    }
}
