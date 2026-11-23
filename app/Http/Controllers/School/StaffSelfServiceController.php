<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffSelfServiceController extends Controller
{
    private function employee(): User
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role, ['teacher', 'admin'], true), 403);

        return $user;
    }

    public function index(): View
    {
        $user = $this->employee();
        $payrolls = StaffPayroll::query()->where('staff_id', $user->id)
            ->where('approval_status', 'approved')->orderByDesc('payroll_month')->limit(36)->get();
        $profile = DB::table('staff_employment_profiles')->where('staff_id', $user->id)->first();
        $documents = DB::table('staff_documents')->where('staff_id', $user->id)->latest()->get();
        $contracts = DB::table('staff_employment_contracts')->where('staff_id', $user->id)->orderByDesc('starts_on')->get();
        $declarations = DB::table('staff_tax_declarations')->where('staff_id', $user->id)->orderByDesc('financial_year')->get();
        $activeYear = now()->month >= 4 ? now()->year.'-'.substr((string) (now()->year + 1), -2) : (now()->year - 1).'-'.substr((string) now()->year, -2);
        $currentDeclaration = $declarations->firstWhere('financial_year', $activeYear);
        $currentDeclarationItems = $currentDeclaration
            ? DB::table('staff_tax_declaration_items')->where('declaration_id', $currentDeclaration->id)->orderBy('id')->get()
            : collect();

        return view('school.staff-self-service.index', compact('user', 'payrolls', 'profile', 'documents', 'contracts', 'declarations', 'activeYear', 'currentDeclaration', 'currentDeclarationItems'));
    }

    public function saveDeclaration(Request $request): RedirectResponse
    {
        $user = $this->employee();
        $data = $request->validate([
            'financial_year' => ['required', 'regex:/^20\d{2}-\d{2}$/'],
            'tax_regime' => ['required', 'in:old,new'],
            'declaration_note' => ['nullable', 'string', 'max:3000'],
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.section_code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/'],
            'items.*.description' => ['nullable', 'string', 'max:180'],
            'items.*.declared_amount' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
        ]);
        $items = collect($data['items'] ?? [])->filter(fn ($item) => collect($item)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())->values();
        foreach ($items as $item) {
            if (blank($item['section_code'] ?? null) || blank($item['description'] ?? null) || ! array_key_exists('declared_amount', $item) || $item['declared_amount'] === null || $item['declared_amount'] === '') {
                throw ValidationException::withMessages(['items' => 'Each declaration line needs a section code, description and amount.']);
            }
        }
        $existing = DB::table('staff_tax_declarations')->where('staff_id', $user->id)->where('financial_year', $data['financial_year'])->first();
        abort_if($existing && in_array($existing->status, ['submitted', 'approved'], true), 409, 'This declaration is already submitted or approved and cannot be edited.');
        DB::transaction(function () use ($user, $data, $existing, $items) {
            $payload = ['tax_regime' => $data['tax_regime'], 'declaration_note' => $data['declaration_note'] ?? null, 'status' => 'draft', 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'updated_at' => now()];
            if ($existing) {
                DB::table('staff_tax_declarations')->where('id', $existing->id)->update($payload);
            } else {
                $id = DB::table('staff_tax_declarations')->insertGetId($payload + ['staff_id' => $user->id, 'financial_year' => $data['financial_year'], 'created_at' => now()]);
            }
            $declarationId = $existing->id ?? $id;
            DB::table('staff_tax_declaration_items')->where('declaration_id', $declarationId)->delete();
            foreach ($items as $item) {
                DB::table('staff_tax_declaration_items')->insert([
                    'declaration_id' => $declarationId, 'section_code' => $item['section_code'],
                    'description' => $item['description'], 'declared_amount' => $item['declared_amount'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Tax declaration draft saved. It has not changed payroll tax or been filed with any authority.');
    }

    public function submitDeclaration(int $declaration): RedirectResponse
    {
        $user = $this->employee();
        $row = DB::table('staff_tax_declarations')->where('id', $declaration)->where('staff_id', $user->id)->first();
        abort_unless($row, 404);
        abort_if($row->status !== 'draft', 409, 'Only a draft declaration can be submitted.');
        DB::table('staff_tax_declarations')->where('id', $row->id)->update(['status' => 'submitted', 'submitted_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Tax declaration submitted for HR review. Submission is not a tax return filing.');
    }

    public function reviewQueue(): View
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $declarations = DB::table('staff_tax_declarations as d')
            ->join('users as u', 'u.id', '=', 'd.staff_id')
            ->where('d.status', 'submitted')
            ->select('d.*', 'u.name as staff_name', 'u.email as staff_email')
            ->orderBy('d.submitted_at')->paginate(30);
        $items = DB::table('staff_tax_declaration_items')->whereIn('declaration_id', $declarations->pluck('id'))->get()->groupBy('declaration_id');

        return view('school.staff-self-service.review-queue', compact('declarations', 'items'));
    }

    public function reviewDeclaration(Request $request, int $declaration): RedirectResponse
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->isAdmin(), 403);
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'review_note' => ['nullable', 'string', 'max:2000']]);
        $row = DB::table('staff_tax_declarations')->where('id', $declaration)->firstOrFail();
        abort_if($row->status !== 'submitted', 409, 'Only submitted declarations can be reviewed.');
        DB::table('staff_tax_declarations')->where('id', $row->id)->update(['status' => $data['decision'], 'review_note' => $data['review_note'] ?? null, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'updated_at' => now()]);

        return back()->with('success', 'Tax declaration review recorded. This does not submit a statutory return.');
    }

    public function downloadDocument(string $kind, int $id): StreamedResponse
    {
        $user = $this->employee();
        abort_unless(in_array($kind, ['document', 'contract'], true), 404);
        $table = $kind === 'document' ? 'staff_documents' : 'staff_employment_contracts';
        $row = DB::table($table)->where('id', $id)->where('staff_id', $user->id)->first();
        abort_unless($row && Storage::disk('local')->exists($row->file_path), 404);

        return Storage::disk('local')->download($row->file_path, $row->original_filename);
    }
}
