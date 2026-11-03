<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'error_details' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
