<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProductionReadinessAuditTest extends TestCase
{
    /**
     * Test that the Production Readiness & Security Audit command runs and yields Release Approval.
     */
    public function test_production_readiness_audit_executes_and_passes_all_gates(): void
    {
        $exitCode = Artisan::call('audit:production');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode, 'Production readiness audit must exit with code 0 (Release Approval)');
        $this->assertStringContainsString('RELEASE APPROVAL: ALL REQUIRED SECURITY & READINESS GATES PASSED', $output);
        $this->assertStringContainsString('TESTS EXECUTED: 29', $output);
        $this->assertStringContainsString('FAILED: 0', $output);

        // Verify JSON and Markdown audit artifacts are written to disk
        $auditFiles = File::glob(storage_path('audit/audit-report-*.json'));
        $this->assertNotEmpty($auditFiles, 'Audit runner must persist machine-readable JSON reports');

        $latestJson = end($auditFiles);
        $data = json_decode(File::get($latestJson), true);

        $this->assertEquals('RELEASE APPROVAL', $data['decision']);
        $this->assertEquals(0, $data['summary']['critical']);
        $this->assertEquals(0, $data['summary']['high']);
        $this->assertGreaterThanOrEqual(25, $data['summary']['passed']);
    }
}
