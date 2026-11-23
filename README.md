# PrimeOS AcadSch — Enterprise School ERP & Learning Management System

A production-grade, full-featured Institutional ERP and Learning Management System (LMS) built with modern Laravel (12.x/13.x) and PHP 8.3+.

---

## Architecture & Modular Boundaries

The codebase strictly enforces domain segregation between **School ERP** and **LMS**:

```
app/
├── Modules/
│   ├── SchoolErp/       # Core ERP domain manifest & models (K-12 operations)
│   └── Lms/             # LMS domain manifest & models (learning activities)
├── Http/
│   ├── Controllers/
│   │   ├── School/      # Canonical School ERP controllers (Fees, Attendance, Students, Exams, Transport)
│   │   ├── Api/         # Sanctum-authenticated JSON API controllers
│   │   └── ...          # Learning Management System controllers (Courses, DPP, Quizzes, Blooms)
│   ├── Requests/
│   │   ├── Auth/        # Authentication & credential form requests
│   │   ├── School/      # ERP Form requests (Fees, Attendance, Students, Academics, Transport)
│   │   └── Lms/         # LMS Form requests (Courses, DPP, Homework, Quizzes)
│   └── Middleware/      # Role-based access control, account verification & sanitization
├── Services/            # Encapsulated domain business logic & calculators
├── Models/              # Eloquent entities & relationships
└── Policies/            # Authorization policies
```

### Route Modularization
Routes are split into dedicated domain files loaded via `bootstrap/app.php`:
- `routes/web.php` — Public portal, marketing pages, verification endpoints.
- `routes/auth.php` — Authentication, registration, password reset, captcha.
- `routes/school.php` — School ERP (Classes, Admissions, Attendance, Fees, Exams, Payroll, Transport).
- `routes/lms.php` — LMS (Courses, Videos, Homework, DPP, Quizzes, Gamification).
- `routes/admin.php` — Institutional settings, onboarding wizard, reports, audit logs.
- `routes/api.php` — REST API v1 for mobile / external client integration.

---

## Installation & Setup

### Prerequisites
- PHP 8.3 or higher with extensions: `pdo_sqlite`, `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
- Composer 2.x
- Node.js & npm (for Vite assets)

### Local / Production Quickstart
```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader

# 2. Environment Configuration
cp .env.example .env
php artisan key:generate

# 3. Database Migration & Master Seed
php artisan migrate --force --seed

# 4. Storage Linking
php artisan storage:link

# 5. Production Optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Testing
Run the comprehensive PHPUnit test suite:
```bash
php artisan test
```

## Code Quality Standards
Code style is enforced via **Laravel Pint** (PSR-12):
```bash
php ./vendor/bin/pint --test
```
