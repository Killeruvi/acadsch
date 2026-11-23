# Contributing & Code Standards Guide

All contributions to **PrimeOS AcadSch** must adhere to the following architectural and coding standards:

---

## 1. Domain Separation
- **School ERP Features** must reside in `App\Http\Controllers\School\*`, `App\Services\*`, and `routes/school.php`.
- **LMS Features** must reside in `App\Http\Controllers\*`, `App\Modules\Lms\*`, and `routes/lms.php`.
- **No Controller Duplication:** Always use the canonical unified controllers (`SchoolFeeController`, `SchoolAttendanceController`, `SchoolStudentController`).

## 2. Validation & Request Handling
- **Zero Inline Validation:** Do not use `$request->validate([...])` inside controller methods.
- Create and inject dedicated **Form Request** classes in `App\Http\Requests\...`.
- Form Requests must define:
  - `authorize(): bool` (RBAC authorization)
  - `rules(): array` (Validation rules)
  - `messages(): array` (Actionable error messages)

## 3. Thin Controllers
- Keep controller methods skinny (< 30 lines where possible).
- Controllers should only:
  1. Receive the typed Form Request.
  2. Invoke a Service / Action class.
  3. Return a View or JSON RedirectResponse.
- **No Direct Database Queries:** Never write raw SQL or complex `DB::table(...)` joins directly inside controllers. Encapsulate database interactions inside Eloquent models, query scopes, or Service classes.

## 4. Blade Components
- Keep individual Blade template files under 10 KB.
- Reuse Blade components (`<x-stat-card>`, `<x-data-table>`, `<x-modal>`, `<x-nav>`, `<x-form-field>`).
- Deconstruct complex layouts into partial views using `@include`.

## 5. Coding Standards & Linter
- All PHP code must adhere strictly to PSR-12 and Laravel styling conventions.
- Run Laravel Pint before committing:
  ```bash
  php ./vendor/bin/pint
  ```
- All automated feature tests must pass without regressions:
  ```bash
  php artisan test
  ```
