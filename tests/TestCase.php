<?php

namespace Tests;

use App\Models\AcademySetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->app && Schema::hasTable('academy_settings')) {
            AcademySetting::set('onboarding_completed', 'true');
        }
    }
}
