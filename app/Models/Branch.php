<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function cashChequeReceipts()
    {
        return $this->hasMany(CashChequeReceipt::class);
    }

    public function personalAccountTransactions()
    {
        return $this->hasMany(PersonalAccountTransaction::class);
    }

    public function auditInspections()
    {
        return $this->hasMany(AuditInspection::class);
    }

    public function feedbackThreads()
    {
        return $this->hasMany(FeedbackThread::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false)->where('is_active', true);
    }
}
