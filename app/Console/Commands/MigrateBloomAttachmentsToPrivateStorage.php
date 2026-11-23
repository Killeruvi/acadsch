<?php

namespace App\Console\Commands;

use App\Models\BloomAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateBloomAttachmentsToPrivateStorage extends Command
{
    protected $signature = 'blooms:migrate-private-attachments {--dry-run : Report changes without copying, updating, or deleting files}';

    protected $description = 'Move legacy Bloom assignment attachments from public to private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $moved = 0;
        $alreadyPrivate = 0;
        $missing = 0;
        $failed = 0;
        $dryRun = (bool) $this->option('dry-run');

        BloomAssignment::query()
            ->where(function ($query) {
                $query->whereNotNull('teacher_attachment_path')
                    ->orWhereNotNull('submission_attachment_path');
            })
            ->orderBy('id')
            ->chunkById(100, function ($assignments) use ($public, $private, $dryRun, &$moved, &$alreadyPrivate, &$missing, &$failed) {
                foreach ($assignments as $assignment) {
                    foreach (['teacher_attachment_path', 'submission_attachment_path'] as $column) {
                        $path = $assignment->{$column};
                        if (! $path || str_contains($path, '..') || str_starts_with($path, '/')) {
                            continue;
                        }

                        if ($private->exists($path)) {
                            $alreadyPrivate++;
                            // If a legacy public copy remains, remove it only after the private copy is confirmed.
                            if (! $dryRun && $public->exists($path)) {
                                $public->delete($path);
                            }

                            continue;
                        }

                        if (! $public->exists($path)) {
                            $this->warn("Missing attachment for assignment {$assignment->id}: {$column}");
                            $missing++;

                            continue;
                        }

                        if ($dryRun) {
                            $this->line("Would migrate assignment {$assignment->id}: {$column}");
                            $moved++;

                            continue;
                        }

                        try {
                            $stream = $public->readStream($path);
                            if (! is_resource($stream)) {
                                throw new \RuntimeException('Unable to read source file stream.');
                            }
                            try {
                                $stored = $private->writeStream($path, $stream);
                            } finally {
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            }

                            if (! $stored || ! $private->exists($path)) {
                                throw new \RuntimeException('Private storage copy could not be verified.');
                            }

                            // Preserve the DB path; authorized downloads now read only from private storage.
                            $public->delete($path);
                            $moved++;
                        } catch (Throwable $exception) {
                            report($exception);
                            $this->error("Failed assignment {$assignment->id} {$column}: {$exception->getMessage()}");
                            $failed++;
                        }
                    }
                }
            });

        $this->newLine();
        $this->info("Migrated/planned: {$moved}; already private: {$alreadyPrivate}; missing: {$missing}; failed: {$failed}");

        if ($missing > 0 || $failed > 0) {
            $this->error('Resolve missing/failed files and rerun before enabling the new release.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
