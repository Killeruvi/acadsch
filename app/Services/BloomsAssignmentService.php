<?php

namespace App\Services;

use App\Models\BloomAssignment;
use App\Models\DailyLesson;
use App\Models\TopicBloomActivity;
use Illuminate\Support\Collection;

class BloomsAssignmentService
{
    /**
     * Normalize string or numeric Bloom's level to an integer between 1 and 6.
     */
    public static function normalizeBloomLevel(mixed $raw): int
    {
        if (is_numeric($raw)) {
            $val = (int) $raw;

            return max(1, min(6, $val));
        }

        $str = strtolower(trim((string) $raw));
        if (str_contains($str, '1') || str_contains($str, 'rememb')) {
            return 1;
        }
        if (str_contains($str, '2') || str_contains($str, 'underst')) {
            return 2;
        }
        if (str_contains($str, '3') || str_contains($str, 'appl')) {
            return 3;
        }
        if (str_contains($str, '4') || str_contains($str, 'analy')) {
            return 4;
        }
        if (str_contains($str, '5') || str_contains($str, 'evaluat')) {
            return 5;
        }
        if (str_contains($str, '6') || str_contains($str, 'creat')) {
            return 6;
        }

        return 1;
    }

    /**
     * Generate and distribute Bloom's assignments to students by roll number.
     *
     * @param  string  $mode  'balanced_random' | 'uniform_level' | 'roll_range'
     * @param  int|null  $targetLevel  (Used if uniform_level is chosen)
     * @return Collection<BloomAssignment>
     */
    public function distributeAssignments(DailyLesson $lesson, string $mode = 'balanced_random', ?int $targetLevel = null): Collection
    {
        // 1. Fetch all pre-mapped Bloom's activities for this topic (Levels 1 to 6)
        $activities = TopicBloomActivity::where('topic_id', $lesson->topic_id)
            ->get()
            ->keyBy('bloom_level');

        // 2. Fetch all enrolled students in the class ordered by roll number
        $enrollments = $lesson->academicClass->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->orderBy('roll_number')
            ->get();

        $studentCount = $enrollments->count();
        if ($studentCount === 0) {
            return collect();
        }

        // Delete any existing assignments for this lesson to allow clean distribution
        BloomAssignment::where('lesson_id', $lesson->id)->delete();

        $assignedList = collect();

        if ($mode === 'uniform_level' && $targetLevel !== null) {
            // Mode A: All students get the exact same teacher-chosen Bloom's level
            foreach ($enrollments as $enrollment) {
                $level = max(1, min(6, $targetLevel));
                $activity = $this->resolveActivity($activities, $level, $lesson);

                $assignment = BloomAssignment::create([
                    'lesson_id' => $lesson->id,
                    'student_id' => $enrollment->student_id,
                    'roll_number' => $enrollment->roll_number,
                    'bloom_level' => $level,
                    'level_name' => $activity['name'],
                    'activity_prompt' => $activity['prompt'],
                    'rubric_guide' => $activity['rubric_guide'] ?? null,
                    'status' => 'assigned',
                ]);

                $assignedList->push($assignment);
            }
        } elseif ($mode === 'roll_range') {
            // Mode B: Sequential Range distribution (Roll 1-2 Level 1, 3-4 Level 2...)
            $chunkSize = max(1, (int) ceil($studentCount / 6));

            foreach ($enrollments as $index => $enrollment) {
                $level = min(6, (int) floor($index / $chunkSize) + 1);
                $activity = $this->resolveActivity($activities, $level, $lesson);

                $assignment = BloomAssignment::create([
                    'lesson_id' => $lesson->id,
                    'student_id' => $enrollment->student_id,
                    'roll_number' => $enrollment->roll_number,
                    'bloom_level' => $level,
                    'level_name' => $activity['name'],
                    'activity_prompt' => $activity['prompt'],
                    'rubric_guide' => $activity['rubric_guide'] ?? null,
                    'status' => 'assigned',
                ]);

                $assignedList->push($assignment);
            }
        } else {
            // Mode C (Default): Balanced Random Shuffled Distribution across Bloom's Levels 1-6
            $levelsPool = [];
            for ($i = 0; $i < $studentCount; $i++) {
                $levelsPool[] = ($i % 6) + 1;
            }

            shuffle($levelsPool);

            foreach ($enrollments as $index => $enrollment) {
                $level = $levelsPool[$index];
                $activity = $this->resolveActivity($activities, $level, $lesson);

                $assignment = BloomAssignment::create([
                    'lesson_id' => $lesson->id,
                    'student_id' => $enrollment->student_id,
                    'roll_number' => $enrollment->roll_number,
                    'bloom_level' => $level,
                    'level_name' => $activity['name'],
                    'activity_prompt' => $activity['prompt'],
                    'rubric_guide' => $activity['rubric_guide'] ?? null,
                    'status' => 'assigned',
                ]);

                $assignedList->push($assignment);
            }
        }

        return $assignedList;
    }

    /**
     * Import custom tasks from parsed CSV rows and randomly assign them across class students.
     *
     * @param  array  $taskRows  Parsed associative CSV rows
     * @param  string  $mode  'random' | 'sequential'
     * @return Collection<BloomAssignment>
     */
    public function importAndAssignTasks(
        DailyLesson $lesson,
        array $taskRows,
        string $mode = 'random',
        bool $clearExisting = true
    ): Collection {
        $enrollments = $lesson->academicClass->enrollments()
            ->where('status', 'active')
            ->with('student')
            ->orderBy('roll_number')
            ->get();

        if ($enrollments->isEmpty() || empty($taskRows)) {
            return collect();
        }

        if ($clearExisting) {
            BloomAssignment::where('lesson_id', $lesson->id)->delete();
        }

        // Clean & normalize tasks from rows
        $cleanedTasks = [];
        foreach ($taskRows as $row) {
            $rawLevel = $row['bloom_level'] ?? ($row['level'] ?? ($row['bloom'] ?? 1));
            $level = self::normalizeBloomLevel($rawLevel);
            $meta = TopicBloomActivity::getLevelMetadata($level);

            $prompt = $row['task_prompt'] ?? ($row['activity_prompt'] ?? ($row['prompt'] ?? ($row['task'] ?? ($row['description'] ?? ''))));
            if (empty(trim($prompt))) {
                continue;
            }

            $levelName = ! empty($row['level_name']) ? trim($row['level_name']) : $meta['name'];
            $rubric = $row['rubric_guide'] ?? ($row['rubric'] ?? ($row['rubric_hint'] ?? null));
            $dueDate = ! empty($row['due_date']) ? date('Y-m-d', strtotime($row['due_date'])) : null;
            $targetedRoll = ! empty($row['roll_number']) ? (int) $row['roll_number'] : null;

            $cleanedTasks[] = [
                'bloom_level' => $level,
                'level_name' => $levelName,
                'activity_prompt' => trim($prompt),
                'rubric_guide' => $rubric ? trim($rubric) : null,
                'due_date' => $dueDate,
                'targeted_roll' => $targetedRoll,
            ];
        }

        if (empty($cleanedTasks)) {
            return collect();
        }

        // Randomize the tasks pool if random mode selected
        if ($mode === 'random') {
            shuffle($cleanedTasks);
        }

        $assignedList = collect();
        $taskCount = count($cleanedTasks);

        foreach ($enrollments as $idx => $enrollment) {
            // Check if there is a task explicitly targeted to this roll number
            $matchedTask = null;
            foreach ($cleanedTasks as $t) {
                if ($t['targeted_roll'] !== null && $t['targeted_roll'] === $enrollment->roll_number) {
                    $matchedTask = $t;
                    break;
                }
            }

            // Otherwise, pick task randomly (cycling if fewer tasks than students)
            if (! $matchedTask) {
                $matchedTask = $cleanedTasks[$idx % $taskCount];
            }

            $assignment = BloomAssignment::create([
                'lesson_id' => $lesson->id,
                'student_id' => $enrollment->student_id,
                'roll_number' => $enrollment->roll_number,
                'bloom_level' => $matchedTask['bloom_level'],
                'level_name' => $matchedTask['level_name'],
                'activity_prompt' => $matchedTask['activity_prompt'],
                'rubric_guide' => $matchedTask['rubric_guide'],
                'due_date' => $matchedTask['due_date'],
                'status' => 'assigned',
            ]);

            $assignedList->push($assignment);
        }

        return $assignedList;
    }

    /**
     * Randomly reshuffle all assignments for a lesson across students.
     */
    public function reshuffleAssignments(DailyLesson $lesson): Collection
    {
        $assignments = BloomAssignment::where('lesson_id', $lesson->id)->get();
        if ($assignments->isEmpty()) {
            return $this->distributeAssignments($lesson, 'balanced_random');
        }

        $enrollments = $lesson->academicClass->enrollments()
            ->where('status', 'active')
            ->orderBy('roll_number')
            ->get();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        // Extract tasks data
        $tasksData = $assignments->map(fn ($a) => [
            'bloom_level' => $a->bloom_level,
            'level_name' => $a->level_name,
            'activity_prompt' => $a->activity_prompt,
            'rubric_guide' => $a->rubric_guide,
            'due_date' => $a->due_date?->toDateString(),
        ])->shuffle()->values();

        // Delete previous and re-create mapped to students
        BloomAssignment::where('lesson_id', $lesson->id)->delete();

        $assignedList = collect();
        $taskCount = $tasksData->count();

        foreach ($enrollments as $idx => $enr) {
            $task = $tasksData[$idx % $taskCount];
            $assignment = BloomAssignment::create([
                'lesson_id' => $lesson->id,
                'student_id' => $enr->student_id,
                'roll_number' => $enr->roll_number,
                'bloom_level' => $task['bloom_level'],
                'level_name' => $task['level_name'],
                'activity_prompt' => $task['activity_prompt'],
                'rubric_guide' => $task['rubric_guide'],
                'due_date' => $task['due_date'],
                'status' => 'assigned',
            ]);
            $assignedList->push($assignment);
        }

        return $assignedList;
    }

    /**
     * Reroll Bloom's level and prompt for a specific student's assignment.
     */
    public function rerollAssignment(BloomAssignment $assignment): BloomAssignment
    {
        $lesson = $assignment->lesson;
        $activities = TopicBloomActivity::where('topic_id', $lesson->topic_id)
            ->get()
            ->keyBy('bloom_level');

        // Choose a different random level (1 to 6)
        $currentLevel = $assignment->bloom_level;
        $otherLevels = array_values(array_filter([1, 2, 3, 4, 5, 6], fn ($l) => $l !== $currentLevel));
        $newLevel = $otherLevels[array_rand($otherLevels)];

        $activity = $this->resolveActivity($activities, $newLevel, $lesson);

        $assignment->update([
            'bloom_level' => $newLevel,
            'level_name' => $activity['name'],
            'activity_prompt' => $activity['prompt'],
            'rubric_guide' => $activity['rubric_guide'] ?? null,
            'submission_content' => null,
            'submitted_at' => null,
            'teacher_feedback' => null,
            'score' => null,
            'status' => 'assigned',
        ]);

        return $assignment;
    }

    /**
     * Retrieve prompt or build fallback prompt for level.
     */
    private function resolveActivity(Collection $activities, int $level, DailyLesson $lesson): array
    {
        $meta = TopicBloomActivity::getLevelMetadata($level);
        $levelName = $meta['name'];

        if (isset($activities[$level])) {
            return [
                'name' => $activities[$level]->level_name,
                'prompt' => $activities[$level]->activity_prompt,
                'rubric_guide' => $activities[$level]->rubric_guide,
            ];
        }

        // Generic fallback prompt based on level verbs and lesson topic
        $topicTitle = $lesson->topic->title;
        $subtopic = $lesson->subtopic ? " ({$lesson->subtopic})" : '';

        $fallbackPrompt = match ($level) {
            1 => "Level 1 (Remember): Define the key terms and list the core facts regarding {$topicTitle}{$subtopic}.",
            2 => "Level 2 (Understand): In your own words, summarize the primary concepts of {$topicTitle}{$subtopic}.",
            3 => "Level 3 (Apply): Apply the concepts of {$topicTitle}{$subtopic} to solve a real-world scenario or design a working test.",
            4 => "Level 4 (Analyze): Differentiate and compare the distinct mechanisms involved in {$topicTitle}{$subtopic}.",
            5 => "Level 5 (Evaluate): Critically evaluate and justify an argument or experimental case study on {$topicTitle}{$subtopic}.",
            6 => "Level 6 (Create): Formulate a creative design, model, or synthesis project based on {$topicTitle}{$subtopic}.",
            default => "Complete the assigned Bloom's Level {$level} activity for {$topicTitle}.",
        };

        return [
            'name' => $levelName,
            'prompt' => $fallbackPrompt,
            'rubric_guide' => "Demonstrate clear comprehension of {$topicTitle} adhering to Level {$level} cognitive rigor.",
        ];
    }
}
