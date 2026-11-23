<?php

namespace App\Console\Commands;

use App\Models\AcademicClass;
use App\Models\AcademicSubject;
use App\Models\Course;
use App\Models\SchoolExamSchedule;
use App\Models\SchoolExamTerm;
use App\Models\SchoolFeeStructure;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuditAdminPagesCommand extends Command
{
    protected $signature = 'audit:admin-pages';

    protected $description = 'Audit all GET routes as authenticated admin and check for 500 errors';

    public function handle(Kernel $kernel): int
    {
        config(['app.debug' => true]);

        $admin = User::where('role', 'admin')->orWhere('email', 'admin@primearc.edu')->first();
        if (! $admin) {
            $this->error('No admin user found!');

            return 1;
        }

        $sampleClass = AcademicClass::first();
        $sampleSubject = AcademicSubject::first();
        $sampleStudent = User::where('role', 'student')->first();
        $sampleTeacher = User::where('role', 'teacher')->first();
        $sampleTerm = SchoolExamTerm::first();
        $sampleFee = SchoolFeeStructure::first();
        $sampleCourse = Course::first();
        $sampleExamSchedule = SchoolExamSchedule::first();

        $sampleIds = [
            'class' => $sampleClass?->id ?? 1,
            'classId' => $sampleClass?->id ?? 1,
            'academicClass' => $sampleClass?->id ?? 1,
            'student' => $sampleStudent?->id ?? 1,
            'studentId' => $sampleStudent?->id ?? 1,
            'teacher' => $sampleTeacher?->id ?? 1,
            'teacherId' => $sampleTeacher?->id ?? 1,
            'subject' => $sampleSubject?->id ?? 1,
            'subjectId' => $sampleSubject?->id ?? 1,
            'term' => $sampleTerm?->id ?? 1,
            'termId' => $sampleTerm?->id ?? 1,
            'fee' => $sampleFee?->id ?? 1,
            'feeId' => $sampleFee?->id ?? 1,
            'course' => $sampleCourse?->id ?? 1,
            'courseId' => $sampleCourse?->id ?? 1,
            'schedule' => $sampleExamSchedule?->id ?? 1,
            'scheduleId' => $sampleExamSchedule?->id ?? 1,
            'session' => '2024-2025',
            'id' => 1,
            'user' => $admin->id,
        ];

        $routes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
        $fails = [];
        $successCount = 0;

        $this->info('Auditing '.count($routes)." GET routes as Admin ({$admin->email})...");

        foreach ($routes as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, '_') || str_starts_with($uri, 'sanctum') || str_contains($uri, 'telescope') || str_contains($uri, 'horizon')) {
                continue;
            }

            $testUri = $uri;
            if (preg_match_all('/\{([a-zA-Z0-9_]+)\??\}/', $uri, $matches)) {
                foreach ($matches[1] as $param) {
                    $val = $sampleIds[$param] ?? 1;
                    $testUri = str_replace(['{'.$param.'}', '{'.$param.'?}'], (string) $val, $testUri);
                }
            }

            $req = Request::create('/'.ltrim($testUri, '/'), 'GET');
            $req->setUserResolver(fn () => $admin);
            app()->instance('request', $req);
            auth('web')->setUser($admin);

            try {
                $response = $kernel->handle($req);
                $status = $response->getStatusCode();
                $kernel->terminate($req, $response);

                if ($status >= 500) {
                    $content = $response->getContent();
                    $errorMsg = '';
                    if (preg_match('/<title>(.*?)<\/title>/is', $content, $m)) {
                        $errorMsg = trim($m[1]);
                    }
                    $fails[] = [
                        'uri' => $uri,
                        'testUri' => $testUri,
                        'status' => $status,
                        'name' => $route->getName(),
                        'preview' => $errorMsg ?: substr(strip_tags($content), 0, 200),
                    ];
                    $this->error("[FAIL $status] $uri ($testUri): $errorMsg");
                } else {
                    $successCount++;
                }
            } catch (\Throwable $e) {
                $fails[] = [
                    'uri' => $uri,
                    'testUri' => $testUri,
                    'status' => 500,
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile().':'.$e->getLine(),
                    'name' => $route->getName(),
                ];
                $this->error("[EXCEPTION] $uri -> {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
            }
        }

        $this->newLine();
        $this->info('==================== AUDIT REPORT ====================');
        $this->info('Total routes checked: '.($successCount + count($fails)));
        $this->info("Successful / Handled: $successCount");
        $this->info('Failed (500 / Exception): '.count($fails));

        if (! empty($fails)) {
            foreach ($fails as $f) {
                $this->line('----------------------------------------');
                $this->line('URI: '.$f['uri'].' (Resolved: '.$f['testUri'].')');
                $this->line('Name: '.($f['name'] ?? 'none'));
                $this->line('Status: '.$f['status']);
                if (isset($f['exception'])) {
                    $this->line('Exception: '.$f['exception']);
                    $this->line('At: '.$f['file']);
                }
                if (isset($f['preview'])) {
                    $this->line('Preview: '.trim(preg_replace('/\s+/', ' ', $f['preview'])));
                }
            }

            return 1;
        }

        $this->info('All routes executed cleanly without 500 errors!');

        return 0;
    }
}
