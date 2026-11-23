<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'title',
        'chapter_number',
        'subtopics',
        'activity_checklist',
        'description',
        'status',
        'completed_at',
        'study_notes',
        'resource_url',
    ];

    protected function casts(): array
    {
        return [
            'subtopics' => 'array',
            'activity_checklist' => 'array',
            'chapter_number' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Default template for chapter activity checklist items.
     */
    public static function defaultActivityChecklistTemplate(): array
    {
        return [
            'lab' => [
                'key' => 'lab',
                'name' => 'Laboratory & Practical Drill',
                'icon' => '🧪',
                'badge' => 'Lab Kit / Experiment',
                'organized' => false,
                'date' => null,
                'notes' => 'Apparatus setup & observations recorded in journal.',
            ],
            'competition' => [
                'key' => 'competition',
                'name' => 'Chapter Olympiad & Problem Sprint',
                'icon' => '🏆',
                'badge' => 'Olympiad / Quiz Sprint',
                'organized' => false,
                'date' => null,
                'notes' => 'Speed problem solving, buzzer quiz, and leaderboard sprint.',
            ],
            'project' => [
                'key' => 'project',
                'name' => 'Hands-on Model & Prototyping',
                'icon' => '🚀',
                'badge' => 'Project / Capstone',
                'organized' => false,
                'date' => null,
                'notes' => 'Construct functional physical apparatus or computational model.',
            ],
            'debate' => [
                'key' => 'debate',
                'name' => 'Academic Parliamentary Debate',
                'icon' => '🗣️',
                'badge' => 'Academic Debate',
                'organized' => false,
                'date' => null,
                'notes' => 'Scientific controversies, ethics, and hypothesis debate.',
            ],
            'symposium' => [
                'key' => 'symposium',
                'name' => 'Research Discovery & Viva Voce',
                'icon' => '🔬',
                'badge' => 'Symposium & Viva',
                'organized' => false,
                'date' => null,
                'notes' => 'Oral defense, student presentation, and peer critique.',
            ],
            'revision' => [
                'key' => 'revision',
                'name' => 'Active Recall & Formula Mind-Map Sprint',
                'icon' => '🔄',
                'badge' => 'Synoptic Revision',
                'organized' => false,
                'date' => null,
                'notes' => 'Formula derivations, flashcards, and high-yield synoptic recap.',
            ],
            'test' => [
                'key' => 'test',
                'name' => 'Chapter Mastery Examination & Error Analysis',
                'icon' => '📝',
                'badge' => 'Chapter Test Mock',
                'organized' => false,
                'date' => null,
                'notes' => 'Timed exam mock under exam conditions with remedial review.',
            ],
        ];
    }

    /**
     * Accessor returning normalized checklist with all 5 core activity types.
     */
    public function getNormalizedActivityChecklistAttribute(): array
    {
        $defaults = self::defaultActivityChecklistTemplate();
        $stored = is_array($this->activity_checklist) ? $this->activity_checklist : [];

        foreach ($defaults as $key => $item) {
            if (isset($stored[$key]) && is_array($stored[$key])) {
                $defaults[$key] = array_merge($item, $stored[$key]);
            }
        }

        return $defaults;
    }

    /**
     * Check if a specific activity type is marked as organized.
     */
    public function isActivityOrganized(string $type): bool
    {
        $list = $this->normalized_activity_checklist;

        return ! empty($list[$type]['organized']);
    }

    /**
     * Total number of organized activities for this chapter.
     */
    public function getOrganizedActivitiesCountAttribute(): int
    {
        $count = 0;
        foreach ($this->normalized_activity_checklist as $act) {
            if (! empty($act['organized'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Activity completion percentage for this chapter (0 to 100).
     */
    public function getActivityProgressPercentAttribute(): float
    {
        $total = count(self::defaultActivityChecklistTemplate());

        return $total > 0 ? round(($this->organized_activities_count / $total) * 100, 1) : 0.0;
    }

    /**
     * Toggle or update an activity's organized state.
     */
    public function toggleActivity(string $type, ?bool $state = null, ?string $date = null, ?string $notes = null): void
    {
        $list = $this->normalized_activity_checklist;
        if (! isset($list[$type])) {
            return;
        }

        $currentState = ! empty($list[$type]['organized']);
        $newState = ($state !== null) ? $state : ! $currentState;

        $list[$type]['organized'] = $newState;
        if ($date !== null) {
            $list[$type]['date'] = $date;
        } elseif ($newState && empty($list[$type]['date'])) {
            $list[$type]['date'] = now()->toDateString();
        }
        if ($notes !== null) {
            $list[$type]['notes'] = $notes;
        }

        $this->activity_checklist = $list;
        $this->save();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'subject_id');
    }

    public function bloomActivities(): HasMany
    {
        return $this->hasMany(TopicBloomActivity::class, 'topic_id')->orderBy('bloom_level');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(DailyLesson::class, 'topic_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(CurriculumVideo::class, 'topic_id')->orderBy('order_index');
    }

    public function dpps(): HasMany
    {
        return $this->hasMany(DailyDpp::class, 'topic_id')->orderBy('assigned_date', 'desc');
    }

    public function homeworkTasks(): HasMany
    {
        return $this->hasMany(HomeworkTask::class, 'topic_id')->orderBy('assigned_date', 'desc');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CurriculumNote::class, 'topic_id')->where('is_published', true);
    }

    public function lessonPlans(): HasMany
    {
        return $this->hasMany(LessonPlan::class, 'topic_id')->orderBy('planned_date')->orderBy('order_index');
    }
}
