<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditCheckpoint extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'carry_marks' => 'decimal:4',
        'weight' => 'decimal:4',
        'is_auto_calculated' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scores()
    {
        return $this->hasMany(InspectionCheckpointScore::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section)->orderBy('sort_order');
    }
}
