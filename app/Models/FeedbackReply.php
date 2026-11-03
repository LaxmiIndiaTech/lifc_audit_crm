<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackReply extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function feedbackThread()
    {
        return $this->belongsTo(FeedbackThread::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
