<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DppQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'dpp_id',
        'question_number',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'explanation',
        'marks',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'marks' => 'integer',
        ];
    }

    public function dpp(): BelongsTo
    {
        return $this->belongsTo(DailyDpp::class, 'dpp_id');
    }
}
