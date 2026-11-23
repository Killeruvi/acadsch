<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffEmploymentController extends Controller
{
    private function admin(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin(), 403);

        return $user;
    }

    private function staff(int $id): User
    {
        return User::query()->whereKey($id)->whereIn('role', ['teacher', 'admin'])->firstOrFail();
    }

    public function show(int $staff): View
    {
        $this->admin();
        $person = $this->staff($staff);

        return view('school.staff-employment.show', [
            'staff' => $person,
            'profile' => DB::table('staff_employment_profiles')->where('staff_id', $person->id)->first(),
            'contracts' => DB::table('staff_employment_contracts')->where('staff_id', $person->id)->latest('starts_on')->get(),
            'documents' => DB::table('staff_documents')->where('staff_id', $person->id)->latest()->get(),
            'tasks' => DB::table('staff_onboarding_tasks')->where('staff_id', $person->id)->orderBy('id')->get(),
        ]);
    }

    public function saveProfile(Request $request, int $staff): RedirectResponse
    {
        $actor = $this->admin();
        $person = $this->staff($staff);
        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('staff_employment_profiles', 'employee_code')->ignore($person->id, 'staff_id')],
            'designation' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', 'in:permanent,probation,contract,part_time,intern'],
            'employment_status' => ['required', 'in:onboarding,active,on_leave,notice_period'],
            'joining_date' => ['required', 'date_format:Y-m-d'],
            'probation_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:joining_date'],
            'work_location' => ['nullable', 'string', 'max:180'],
            'emergency_contact_name' => ['nullable', 'string', 'max:160'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $existing = DB::table('staff_employment_profiles')->where('staff_id', $person->id)->first();
        if (($data['employment_status'] ?? null) === 'active') {
            abort_unless($existing, 409, 'Create the employment profile and complete onboarding before activating this staff member.');
            $incomplete = DB::table('staff_onboarding_tasks')->where('staff_id', $person->id)->where('is_required', true)->whereNull('completed_at')->exists();
            abort_if($incomplete, 409, 'All required onboarding tasks must be completed before employment can be marked active.');
        }
        $payload = $data + ['created_by' => $existing->created_by ?? $actor->id, 'updated_at' => now()];
        if ($existing) {
            DB::table('staff_employment_profiles')->where('id', $existing->id)->update($payload);
        } else {
            DB::table('staff_employment_profiles')->insert($payload + ['staff_id' => $person->id, 'employment_status' => 'onboarding', 'created_at' => now()]);
        }
        $defaultTasks = [
            'identity_verified' => 'Verify identity and address documents',
            'qualifications_verified' => 'Verify educational and professional qualifications',
            'references_checked' => 'Complete reference and background checks where applicable',
            'contract_signed' => 'Issue and obtain signed employment contract',
            'bank_tax_details' => 'Verify bank and tax details through approved process',
            'policy_acknowledgement' => 'Obtain staff handbook and policy acknowledgements',
            'system_access' => 'Provision role-appropriate system access',
            'orientation' => 'Complete institutional orientation',
        ];
        foreach ($defaultTasks as $code => $title) {
            DB::table('staff_onboarding_tasks')->insertOrIgnore([
                'staff_id' => $person->id, 'task_code' => $code, 'title' => $title, 'is_required' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->audit('employment_profile', $person->id, 'saved', $actor->id, null, $data);

        return back()->with('success', 'Employment profile saved. Complete the onboarding checklist before activating employment.');
    }

    public function uploadContract(Request $request, int $staff): RedirectResponse
    {
        $actor = $this->admin();
        $person = $this->staff($staff);
        $data = $request->validate([
            'contract_number' => ['required', 'string', 'max:60', 'unique:staff_employment_contracts,contract_number'],
            'contract_type' => ['required', 'string', 'max:80'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'monthly_ctc' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['required', 'in:draft,active'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);
        $path = $request->file('file')->store('staff/'.$person->id.'/contracts', 'local');
        abort_if(! $path, 500, 'The contract could not be stored securely.');
        try {
            DB::table('staff_employment_contracts')->insert([
                'staff_id' => $person->id, 'contract_number' => trim($data['contract_number']), 'contract_type' => $data['contract_type'],
                'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'] ?? null, 'monthly_ctc' => $data['monthly_ctc'] ?? null,
                'currency' => 'INR', 'file_path' => $path, 'original_filename' => basename($request->file('file')->getClientOriginalName()),
                'mime_type' => 'application/pdf', 'file_size' => $request->file('file')->getSize(), 'status' => $data['status'],
                'uploaded_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        $this->audit('employment_contract', $person->id, 'uploaded', $actor->id, null, ['contract_number' => $data['contract_number']]);

        return back()->with('success', 'Employment contract uploaded to private storage.');
    }

    public function uploadDocument(Request $request, int $staff): RedirectResponse
    {
        $actor = $this->admin();
        $person = $this->staff($staff);
        $data = $request->validate([
            'document_type' => ['required', 'in:identity,address,qualification,experience,bank,tax,police_verification,other'],
            'document_name' => ['required', 'string', 'max:180'],
            'expires_on' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);
        $file = $request->file('file');
        $path = $file->store('staff/'.$person->id.'/documents', 'local');
        abort_if(! $path, 500, 'The document could not be stored securely.');
        try {
            DB::table('staff_documents')->insert([
                'staff_id' => $person->id, 'document_type' => $data['document_type'], 'document_name' => $data['document_name'],
                'file_path' => $path, 'original_filename' => basename($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType(), 'file_size' => $file->getSize(), 'expires_on' => $data['expires_on'] ?? null,
                'notes' => $data['notes'] ?? null, 'uploaded_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
        $this->audit('staff_document', $person->id, 'uploaded', $actor->id, null, ['document_type' => $data['document_type'], 'document_name' => $data['document_name']]);

        return back()->with('success', 'Staff document uploaded securely.');
    }

    public function downloadContract(int $staff, int $contract): StreamedResponse
    {
        $this->admin();
        $row = DB::table('staff_employment_contracts')->where('id', $contract)->where('staff_id', $staff)->first();
        abort_unless($row && Storage::disk('local')->exists($row->file_path), 404);

        return Storage::disk('local')->download($row->file_path, $row->original_filename, ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function downloadDocument(int $staff, int $document): StreamedResponse
    {
        $this->admin();
        $row = DB::table('staff_documents')->where('id', $document)->where('staff_id', $staff)->first();
        abort_unless($row && Storage::disk('local')->exists($row->file_path), 404);

        return Storage::disk('local')->download($row->file_path, $row->original_filename, ['Content-Type' => $row->mime_type, 'X-Content-Type-Options' => 'nosniff']);
    }

    public function updateOnboardingTask(Request $request, int $staff, int $task): RedirectResponse
    {
        $actor = $this->admin();
        $person = $this->staff($staff);
        $data = $request->validate(['completed' => ['required', 'boolean'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $row = DB::table('staff_onboarding_tasks')->where('id', $task)->where('staff_id', $person->id)->first();
        abort_unless($row, 404);
        DB::table('staff_onboarding_tasks')->where('id', $task)->update([
            'completed_at' => $data['completed'] ? now() : null,
            'completed_by' => $data['completed'] ? $actor->id : null,
            'notes' => isset($data['notes']) ? trim($data['notes']) : null,
            'updated_at' => now(),
        ]);
        $this->audit('onboarding_task', $task, $data['completed'] ? 'completed' : 'reopened', $actor->id,
            ['completed_at' => $row->completed_at], ['completed' => (bool) $data['completed'], 'notes' => $data['notes'] ?? null]);

        return back()->with('success', 'Onboarding checklist updated.');
    }

    public function separate(Request $request, int $staff): RedirectResponse
    {
        $actor = $this->admin();
        $person = $this->staff($staff);
        abort_if($person->id === $actor->id, 422, 'An administrator cannot separate their own account.');
        $data = $request->validate([
            'last_working_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'separation_reason' => ['required', 'string', 'min:5', 'max:2000'],
            'confirm_email' => ['required', 'email', 'in:'.$person->email],
        ]);
        DB::transaction(function () use ($person, $actor, $data) {
            $profile = DB::table('staff_employment_profiles')->where('staff_id', $person->id)->lockForUpdate()->first();
            abort_unless($profile, 409, 'Create the employment profile before separating this staff member.');
            abort_if($profile->employment_status === 'separated', 409, 'This staff member is already separated.');
            DB::table('staff_employment_profiles')->where('staff_id', $person->id)->update([
                'employment_status' => 'separated', 'last_working_date' => $data['last_working_date'], 'updated_at' => now(),
                'admin_notes' => trim(($profile->admin_notes ? $profile->admin_notes."\n" : '').'Separation: '.$data['separation_reason']),
            ]);
            DB::table('staff_employment_contracts')->where('staff_id', $person->id)->where('status', 'active')->update(['status' => 'terminated', 'updated_at' => now()]);
            $person->forceFill(['is_approved' => false])->save();
            DB::table('sessions')->where('user_id', $person->id)->delete();
            $this->audit('employment_profile', $person->id, 'separated', $actor->id, ['employment_status' => $profile->employment_status], ['employment_status' => 'separated', 'last_working_date' => $data['last_working_date'], 'reason' => $data['separation_reason']]);
        }, 3);

        return redirect()->route('staff.show', $person->id)->with('success', 'Staff account access disabled and employment marked separated. Historical records were retained.');
    }

    private function audit(string $type, int $id, string $event, int $actor, ?array $before, ?array $after): void
    {
        DB::table('staff_hr_audits')->insert([
            'entity_type' => $type, 'entity_id' => $id, 'actor_id' => $actor, 'event_type' => $event,
            'before_data' => $before ? json_encode($before, JSON_THROW_ON_ERROR) : null,
            'after_data' => $after ? json_encode($after, JSON_THROW_ON_ERROR) : null, 'created_at' => now(),
        ]);
    }
}
