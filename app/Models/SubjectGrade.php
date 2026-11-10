<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectGrade extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function result(): BelongsTo
    {
        return $this->belongsTo(AcademicResult::class, 'result_id');
    }
}
