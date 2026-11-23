<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicBloomActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'bloom_level',
        'level_name',
        'verb_keywords',
        'activity_prompt',
        'rubric_guide',
    ];

    protected function casts(): array
    {
        return [
            'bloom_level' => 'integer',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CurriculumTopic::class, 'topic_id');
    }

    /**
     * Standard names and color themes for the 6 Bloom's Taxonomy levels.
     */
    public static function getLevelMetadata(int $level): array
    {
        return match ($level) {
            1 => [
                'name' => 'Remember',
                'description' => 'Recall facts and basic concepts',
                'verbs' => 'Define, duplicate, list, memorize, repeat, state',
                'badge_color' => 'bg-slate-100 text-slate-800 border-slate-300',
                'accent_color' => '#64748b',
            ],
            2 => [
                'name' => 'Understand',
                'description' => 'Explain ideas or concepts',
                'verbs' => 'Classify, describe, discuss, explain, identify, locate, recognize',
                'badge_color' => 'bg-blue-100 text-blue-800 border-blue-300',
                'accent_color' => '#3b82f6',
            ],
            3 => [
                'name' => 'Apply',
                'description' => 'Use information in new situations',
                'verbs' => 'Execute, implement, solve, use, demonstrate, interpret',
                'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'accent_color' => '#10b981',
            ],
            4 => [
                'name' => 'Analyze',
                'description' => 'Draw connections among ideas',
                'verbs' => 'Differentiate, organize, relate, compare, contrast, examine',
                'badge_color' => 'bg-amber-100 text-amber-800 border-amber-300',
                'accent_color' => '#f59e0b',
            ],
            5 => [
                'name' => 'Evaluate',
                'description' => 'Justify a stand or decision',
                'verbs' => 'Appraise, argue, defend, judge, select, support, critique',
                'badge_color' => 'bg-purple-100 text-purple-800 border-purple-300',
                'accent_color' => '#8b5cf6',
            ],
            6 => [
                'name' => 'Create',
                'description' => 'Produce new or original work',
                'verbs' => 'Design, assemble, construct, conjecture, develop, formulate',
                'badge_color' => 'bg-rose-100 text-rose-800 border-rose-300',
                'accent_color' => '#f43f5e',
            ],
            default => [
                'name' => 'Bloom Level '.$level,
                'description' => 'Cognitive Task',
                'verbs' => 'Perform task',
                'badge_color' => 'bg-slate-100 text-slate-800 border-slate-300',
                'accent_color' => '#64748b',
            ],
        };
    }
}
