<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionCheckpointScore extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'carry_marks' => 'decimal:4',
        'marks_obtained' => 'decimal:4',
        'percentage' => 'decimal:4',
        'weight' => 'decimal:4',
        'calculation_data' => 'array',
    ];

    public function inspection()
    {
        return $this->belongsTo(AuditInspection::class, 'audit_inspection_id');
    }

    public function checkpoint()
    {
        return $this->belongsTo(AuditCheckpoint::class, 'audit_checkpoint_id');
    }
}
