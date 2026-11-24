<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\SchoolPeriod;
use App\Models\SchoolSection;
use App\Models\SchoolSetting;
use App\Models\SubjectMaster;
use App\Models\User;
use App\Services\SchoolBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InstallController extends Controller
{
    /**
     * Determine current installation step or route appropriately.
     */
    public function index(): RedirectResponse|View
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login')->with('info', 'Application is already installed.');
        }

        $req = $this->checkRequirements();
        if (! $req['all_passed']) {
            return redirect()->route('install.requirements');
        }

        if (! $this->isDatabaseConfiguredAndMigrated()) {
            return redirect()->route('install.database');
        }

        if (! $this->isBrandingConfigured()) {
            return redirect()->route('install.branding');
        }

        if (! $this->isAdminConfigured()) {
            return redirect()->route('install.admin');
        }

        return redirect()->route('install.complete');
    }

    /**
     * Step 1: Server Requirements & Directory Permissions
     */
    public function stepRequirements(): View|RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        $requirements = $this->checkRequirements();

        return view('install.requirements', compact('requirements'));
    }

    /**
     * Step 2: Database Configuration View
     */
    public function stepDatabase(): View|RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        $req = $this->checkRequirements();
        if (! $req['all_passed']) {
            return redirect()->route('install.requirements')
                ->with('error', 'Please resolve the server requirements and permissions before proceeding.');
        }

        $currentDb = [
            'connection' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', ''),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
        ];

        return view('install.database', compact('currentDb'));
    }

    /**
     * Step 2: Process Database Configuration & Migrations
     */
    public function postDatabase(Request $request): RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'db_connection' => 'required|in:mysql,sqlite',
            'db_host' => 'required_if:db_connection,mysql',
            'db_port' => 'required_if:db_connection,mysql',
            'db_database' => 'required',
            'db_username' => 'required_if:db_connection,mysql',
            'db_password' => 'nullable',
        ]);

        $connection = $validated['db_connection'];
        $host = $validated['db_host'] ?? '127.0.0.1';
        $port = $validated['db_port'] ?? '3306';
        $database = $validated['db_database'];
        $username = $validated['db_username'] ?? '';
        $password = $validated['db_password'] ?? '';

        // 1. Verify connection using raw PDO before updating anything
        try {
            if ($connection === 'mysql') {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
                $pdo = new \PDO($dsn, $username, $password, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_TIMEOUT => 5,
                ]);
            } else {
                if (! file_exists($database) && $database !== ':memory:') {
                    touch($database);
                }
                $dsn = "sqlite:{$database}";
                $pdo = new \PDO($dsn, null, null, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]);
            }
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Database Connection Failed: ' . $e->getMessage());
        }

        // 2. Persist database configuration into .env
        $this->updateEnv([
            'DB_CONNECTION' => $connection,
            'DB_HOST' => $connection === 'mysql' ? $host : '',
            'DB_PORT' => $connection === 'mysql' ? $port : '',
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $connection === 'mysql' ? $username : '',
            'DB_PASSWORD' => $connection === 'mysql' ? $password : '',
        ]);

        // 3. Configure runtime settings and reconnect
        config([
            'database.default' => $connection,
            "database.connections.{$connection}.database" => $database,
        ]);
        if ($connection === 'mysql') {
            config([
                'database.connections.mysql.host' => $host,
                'database.connections.mysql.port' => $port,
                'database.connections.mysql.username' => $username,
                'database.connections.mysql.password' => $password,
            ]);
        }

        DB::purge($connection);
        DB::reconnect($connection);

        // 4. Ensure Application Encryption Key exists
        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        // 5. Execute production database schema migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Migration Failed: ' . $e->getMessage());
        }

        return redirect()->route('install.branding')
            ->with('success', 'Database connected and database tables migrated successfully!');
    }

    /**
     * Step 3: Institutional White-Label Branding Setup
     */
    public function stepBranding(): View|RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        if (! $this->isDatabaseConfiguredAndMigrated()) {
            return redirect()->route('install.database')
                ->with('error', 'Please configure your database first.');
        }

        $branding = SchoolBrandingService::get();

        return view('install.branding', compact('branding'));
    }

    /**
     * Step 3: Process Institutional Branding Submission
     */
    public function postBranding(Request $request): RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'institute_name' => 'required|string|max:255',
            'school_short_name' => 'nullable|string|max:15',
            'motto' => 'nullable|string|max:255',
            'board_name' => 'required|string|max:100',
            'affiliation_number' => 'nullable|string|max:100',
            'campus_name' => 'required|string|max:100',
            'principal_name' => 'nullable|string|max:150',
            'institution_email' => 'required|email|max:150',
            'institution_phone' => 'nullable|string|max:50',
            'institution_address' => 'nullable|string|max:500',
            'currency_symbol' => 'required|string|max:10',
            'currency_code' => 'required|string|max:10',
            'brand_primary_color' => 'required|string|max:20',
            'school_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        // Process custom logo upload
        $logoUrl = null;
        if ($request->hasFile('school_logo')) {
            $file = $request->file('school_logo');
            $fileName = 'school_logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $fileName);
            $logoUrl = '/uploads/branding/' . $fileName;
        }

        $settings = [
            'institute_name' => $validated['institute_name'],
            'school_short_name' => $validated['school_short_name'] ?? '',
            'motto' => $validated['motto'] ?? '',
            'board_name' => $validated['board_name'],
            'affiliation_number' => $validated['affiliation_number'] ?? '',
            'campus_name' => $validated['campus_name'],
            'principal_name' => $validated['principal_name'] ?? 'Principal Office',
            'institution_email' => $validated['institution_email'],
            'institution_phone' => $validated['institution_phone'] ?? '',
            'institution_address' => $validated['institution_address'] ?? '',
            'currency_symbol' => $validated['currency_symbol'],
            'currency_code' => strtoupper($validated['currency_code']),
            'brand_primary_color' => $validated['brand_primary_color'],
            'theme_primary_color' => $validated['brand_primary_color'],
        ];

        if ($logoUrl) {
            $settings['school_logo_url'] = $logoUrl;
        }

        foreach ($settings as $key => $value) {
            SchoolSetting::set($key, (string) $value);
        }

        $this->updateEnv([
            'APP_NAME' => $validated['institute_name'],
            'MAIL_FROM_NAME' => $validated['institute_name'],
            'MAIL_FROM_ADDRESS' => $validated['institution_email'],
        ]);

        SchoolBrandingService::flush();

        return redirect()->route('install.admin')
            ->with('success', 'Institutional branding configured successfully!');
    }

    /**
     * Step 4: Master Super Admin Provisioning & Baseline Structure
     */
    public function stepAdmin(): View|RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        if (! $this->isDatabaseConfiguredAndMigrated()) {
            return redirect()->route('install.database');
        }

        $currentYear = (int) date('Y');
        $defaultSession = $currentYear . '–' . ($currentYear + 1);

        return view('install.admin', compact('defaultSession'));
    }

    /**
     * Step 4: Process Super Admin Setup & Baseline Provisioning
     */
    public function postAdmin(Request $request): RedirectResponse
    {
        if (file_exists(storage_path('installed'))) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|confirmed',
            'academic_session' => 'required|string|max:30',
            'provision_k12_structure' => 'nullable|boolean',
        ]);

        // 1. Create Master Super Administrator
        $admin = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['name'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'is_approved' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Set academic session & mark onboarding completed
        SchoolSetting::set('academic_session', $validated['academic_session']);
        SchoolSetting::set('onboarding_completed', 'true');
        SchoolSetting::set('onboarding_completed_at', now()->toIso8601String());

        // 3. Provision clean, production-grade K-12 academic structure if requested
        if ($request->boolean('provision_k12_structure', true)) {
            $this->provisionCleanSchoolStructure($validated['academic_session']);
        }

        // 4. Secure the installation with permanent lockfile
        $installPayload = [
            'installed_at' => now()->toIso8601String(),
            'version' => '2.5.0',
            'admin_email' => $admin->email,
            'school_name' => SchoolSetting::get('institute_name', 'School ERP'),
            'app_url' => url('/'),
        ];

        file_put_contents(storage_path('installed'), json_encode($installPayload, JSON_PRETTY_PRINT));

        // 5. Authenticate master admin into the portal
        Auth::login($admin);

        return redirect()->route('install.complete');
    }

    /**
     * Step 5: Installation Complete Summary View
     */
    public function stepComplete(): View|RedirectResponse
    {
        if (! file_exists(storage_path('installed'))) {
            return redirect()->route('install.index');
        }

        $meta = [];
        $raw = @file_get_contents(storage_path('installed'));
        if ($raw) {
            $meta = json_decode($raw, true) ?: [];
        }

        $branding = SchoolBrandingService::get();
        $admin = Auth::user();

        return view('install.complete', compact('meta', 'branding', 'admin'));
    }

    /**
     * Provision clean, production-grade school structure without dummy users or fake marks.
     */
    protected function provisionCleanSchoolStructure(string $session): void
    {
        // 1. Default Sections
        $sections = ['A', 'B'];
        $sectionIds = [];
        foreach ($sections as $sec) {
            $section = SchoolSection::firstOrCreate(
                ['name_key' => strtolower($sec)],
                [
                    'name' => "Section {$sec}",
                    'capacity' => 40,
                    'is_active' => true,
                ]
            );
            $sectionIds[$sec] = $section->id;
        }

        // 2. Standard Grades / Classes
        $gradeDefinitions = [
            ['name' => 'Nursery', 'grade_level' => 0],
            ['name' => 'LKG', 'grade_level' => 0],
            ['name' => 'UKG', 'grade_level' => 0],
            ['name' => 'Class 1', 'grade_level' => 1],
            ['name' => 'Class 2', 'grade_level' => 2],
            ['name' => 'Class 3', 'grade_level' => 3],
            ['name' => 'Class 4', 'grade_level' => 4],
            ['name' => 'Class 5', 'grade_level' => 5],
            ['name' => 'Class 6', 'grade_level' => 6],
            ['name' => 'Class 7', 'grade_level' => 7],
            ['name' => 'Class 8', 'grade_level' => 8],
            ['name' => 'Class 9', 'grade_level' => 9],
            ['name' => 'Class 10', 'grade_level' => 10],
            ['name' => 'Class 11 - Science', 'grade_level' => 11, 'stream' => 'Science'],
            ['name' => 'Class 11 - Commerce', 'grade_level' => 11, 'stream' => 'Commerce'],
            ['name' => 'Class 11 - Humanities', 'grade_level' => 11, 'stream' => 'Humanities'],
            ['name' => 'Class 12 - Science', 'grade_level' => 12, 'stream' => 'Science'],
            ['name' => 'Class 12 - Commerce', 'grade_level' => 12, 'stream' => 'Commerce'],
            ['name' => 'Class 12 - Humanities', 'grade_level' => 12, 'stream' => 'Humanities'],
        ];

        foreach ($gradeDefinitions as $g) {
            $class = AcademicClass::firstOrCreate(
                [
                    'name' => $g['name'],
                    'academic_year' => $session,
                ],
                [
                    'grade_level' => $g['grade_level'],
                    'stream' => $g['stream'] ?? 'General',
                    'section' => 'A',
                    'section_id' => $sectionIds['A'] ?? null,
                    'capacity' => 40,
                ]
            );
        }

        // 3. Standard Subject Masters
        $subjects = [
            ['name' => 'English Language & Literature', 'code' => 'ENG', 'category' => 'Core', 'min_grade' => 1, 'max_grade' => 12],
            ['name' => 'Mathematics', 'code' => 'MATH', 'category' => 'Core', 'min_grade' => 1, 'max_grade' => 12],
            ['name' => 'Environmental Studies & Science', 'code' => 'SCI', 'category' => 'Core', 'min_grade' => 1, 'max_grade' => 10],
            ['name' => 'Social Studies', 'code' => 'SST', 'category' => 'Core', 'min_grade' => 1, 'max_grade' => 10],
            ['name' => 'Computer Science & AI', 'code' => 'CS', 'category' => 'Skill', 'min_grade' => 1, 'max_grade' => 12],
            ['name' => 'Physical Health & Sports', 'code' => 'PHE', 'category' => 'Co-Curricular', 'min_grade' => 1, 'max_grade' => 12],
            ['name' => 'Art & Visual Expression', 'code' => 'ART', 'category' => 'Co-Curricular', 'min_grade' => 1, 'max_grade' => 10],
            ['name' => 'Physics', 'code' => 'PHY', 'category' => 'Elective', 'min_grade' => 11, 'max_grade' => 12, 'stream' => 'Science'],
            ['name' => 'Chemistry', 'code' => 'CHEM', 'category' => 'Elective', 'min_grade' => 11, 'max_grade' => 12, 'stream' => 'Science'],
            ['name' => 'Biology', 'code' => 'BIO', 'category' => 'Elective', 'min_grade' => 11, 'max_grade' => 12, 'stream' => 'Science'],
            ['name' => 'Accountancy', 'code' => 'ACC', 'category' => 'Elective', 'min_grade' => 11, 'max_grade' => 12, 'stream' => 'Commerce'],
            ['name' => 'Business Studies', 'code' => 'BST', 'category' => 'Elective', 'min_grade' => 11, 'max_grade' => 12, 'stream' => 'Commerce'],
            ['name' => 'Economics', 'code' => 'ECO', 'category' => 'Elective', 'min_grade' => 11, 'max_grade' => 12, 'stream' => 'Commerce'],
        ];

        foreach ($subjects as $s) {
            SubjectMaster::firstOrCreate(
                ['code' => $s['code']],
                [
                    'name' => $s['name'],
                    'category' => $s['category'],
                    'stream' => $s['stream'] ?? 'General',
                    'min_grade' => $s['min_grade'],
                    'max_grade' => $s['max_grade'],
                    'is_active' => true,
                    'default_periods' => 5,
                ]
            );
        }

        // 4. Standard Daily School Period Structure (8 periods + Breaks)
        $periods = [
            ['period_number' => 1, 'name' => 'Period 1', 'start_time' => '08:30', 'end_time' => '09:15', 'is_break' => false, 'sort_order' => 1],
            ['period_number' => 2, 'name' => 'Period 2', 'start_time' => '09:15', 'end_time' => '10:00', 'is_break' => false, 'sort_order' => 2],
            ['period_number' => 3, 'name' => 'Period 3', 'start_time' => '10:00', 'end_time' => '10:45', 'is_break' => false, 'sort_order' => 3],
            ['period_number' => 0, 'name' => 'Morning Recess', 'start_time' => '10:45', 'end_time' => '11:00', 'is_break' => true, 'sort_order' => 4],
            ['period_number' => 4, 'name' => 'Period 4', 'start_time' => '11:00', 'end_time' => '11:45', 'is_break' => false, 'sort_order' => 5],
            ['period_number' => 5, 'name' => 'Period 5', 'start_time' => '11:45', 'end_time' => '12:30', 'is_break' => false, 'sort_order' => 6],
            ['period_number' => 0, 'name' => 'Lunch Break', 'start_time' => '12:30', 'end_time' => '13:15', 'is_break' => true, 'sort_order' => 7],
            ['period_number' => 6, 'name' => 'Period 6', 'start_time' => '13:15', 'end_time' => '14:00', 'is_break' => false, 'sort_order' => 8],
            ['period_number' => 7, 'name' => 'Period 7', 'start_time' => '14:00', 'end_time' => '14:45', 'is_break' => false, 'sort_order' => 9],
            ['period_number' => 8, 'name' => 'Period 8', 'start_time' => '14:45', 'end_time' => '15:30', 'is_break' => false, 'sort_order' => 10],
        ];

        foreach ($periods as $p) {
            SchoolPeriod::firstOrCreate(
                ['name' => $p['name'], 'start_time' => $p['start_time']],
                [
                    'period_number' => $p['period_number'],
                    'end_time' => $p['end_time'],
                    'is_break' => $p['is_break'],
                    'sort_order' => $p['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Check if database connection and essential tables are ready.
     */
    protected function isDatabaseConfiguredAndMigrated(): bool
    {
        try {
            DB::connection()->getPdo();
            return Schema::hasTable('academy_settings') && Schema::hasTable('users');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if institutional branding has been defined.
     */
    protected function isBrandingConfigured(): bool
    {
        try {
            if (! Schema::hasTable('academy_settings')) {
                return false;
            }
            $name = SchoolSetting::get('institute_name');
            return ! empty($name);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if master admin exists.
     */
    protected function isAdminConfigured(): bool
    {
        try {
            if (! Schema::hasTable('users')) {
                return false;
            }
            return User::where('role', 'admin')->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Evaluate server prerequisites and file permissions.
     */
    protected function checkRequirements(): array
    {
        $phpVersion = PHP_VERSION;
        $phpSatisfied = version_compare($phpVersion, '8.2.0', '>=');

        $requiredExtensions = [
            'pdo' => 'PDO Data Objects',
            'openssl' => 'OpenSSL Cryptography',
            'mbstring' => 'Multibyte String Support',
            'tokenizer' => 'PHP Tokenizer',
            'xml' => 'XML Parsing Engine',
            'ctype' => 'Character Type Functions',
            'json' => 'JSON Parser & Encoder',
            'bcmath' => 'BCMath Arbitrary Precision',
            'fileinfo' => 'File Information Support',
            'curl' => 'cURL Network Client',
        ];

        $extensionResults = [];
        $extensionsPassed = true;
        foreach ($requiredExtensions as $ext => $label) {
            $loaded = extension_loaded($ext);
            $extensionResults[$ext] = [
                'name' => $label,
                'satisfied' => $loaded,
            ];
            if (! $loaded) {
                $extensionsPassed = false;
            }
        }

        // Database driver check: pdo_mysql or pdo_sqlite
        $hasMysql = extension_loaded('pdo_mysql');
        $hasSqlite = extension_loaded('pdo_sqlite');
        $dbDriverSatisfied = $hasMysql || $hasSqlite;
        $extensionResults['db_driver'] = [
            'name' => 'Database Driver (PDO MySQL or SQLite)',
            'satisfied' => $dbDriverSatisfied,
        ];
        if (! $dbDriverSatisfied) {
            $extensionsPassed = false;
        }

        $directories = [
            storage_path() => 'storage/',
            storage_path('app') => 'storage/app/',
            storage_path('framework') => 'storage/framework/',
            storage_path('framework/cache') => 'storage/framework/cache/',
            storage_path('framework/sessions') => 'storage/framework/sessions/',
            storage_path('framework/views') => 'storage/framework/views/',
            storage_path('logs') => 'storage/logs/',
            base_path('bootstrap/cache') => 'bootstrap/cache/',
        ];

        $directoryResults = [];
        $directoriesPassed = true;
        foreach ($directories as $path => $label) {
            if (! file_exists($path)) {
                @mkdir($path, 0775, true);
            }
            $writable = is_writable($path);
            $directoryResults[$label] = [
                'path' => $label,
                'satisfied' => $writable,
            ];
            if (! $writable) {
                $directoriesPassed = false;
            }
        }

        // Environment file check
        $envPath = base_path('.env');
        $envWritable = false;
        if (file_exists($envPath)) {
            $envWritable = is_writable($envPath);
        } else {
            $envWritable = is_writable(base_path());
        }

        $allPassed = $phpSatisfied && $extensionsPassed && $directoriesPassed && $envWritable;

        return [
            'php_version' => $phpVersion,
            'php_satisfied' => $phpSatisfied,
            'extensions' => $extensionResults,
            'extensions_passed' => $extensionsPassed,
            'directories' => $directoryResults,
            'directories_passed' => $directoriesPassed,
            'env_writable' => $envWritable,
            'all_passed' => $allPassed,
        ];
    }

    /**
     * Safely update or append keys in .env
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $envPath);
            } else {
                touch($envPath);
            }
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $value = (string) $value;
            if (preg_match('/\s/', $value) || str_contains($value, '"') || str_contains($value, "'")) {
                $formattedValue = '"' . addcslashes($value, '"\\') . '"';
            } else {
                $formattedValue = $value;
            }

            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$formattedValue}", $content);
            } else {
                $content .= "\n{$key}={$formattedValue}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
