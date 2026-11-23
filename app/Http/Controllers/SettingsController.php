<?php

namespace App\Http\Controllers;

use App\Models\AcademySetting;
use App\Models\SchoolAdminAudit;
use App\Models\SchoolComplianceDocument;
use App\Services\SchoolBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $settings = AcademySetting::pluck('value', 'key')->toArray();
        $complianceDocuments = SchoolComplianceDocument::query()
            ->orderByRaw('CASE WHEN expires_on IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expires_on')
            ->orderBy('title')
            ->get();
        $complianceSummary = [
            'expired'  => $complianceDocuments->filter(fn ($d) => $d->expiry_status === 'expired')->count(),
            'expiring' => $complianceDocuments->filter(fn ($d) => $d->expiry_status === 'expiring')->count(),
        ];
        $qrImageUrl = null;
        $qrPath = $settings['payment_qr_image'] ?? null;
        if ($qrPath && Storage::disk('public')->exists($qrPath)) {
            $qrImageUrl = Storage::disk('public')->url($qrPath);
        }

        return view('settings.index', compact('settings', 'complianceDocuments', 'complianceSummary', 'qrImageUrl'));
    }

    /**
     * Update school settings. No GST, no payment gateway, no OwnPay.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Only administrators can update school settings.');
        }

        $validated = $request->validate([
            'institute_name'                  => 'required|string|max:255',
            'school_short_name'               => 'nullable|string|max:20',
            'campus_name'                     => 'required|string|max:255',
            'motto'                           => 'nullable|string|max:255',
            'school_logo_url'                 => 'nullable|string|max:1000',
            'school_favicon_url'              => 'nullable|string|max:1000',
            'board_name'                      => 'nullable|string|max:50',
            'affiliation_number'              => 'nullable|string|max:255',
            'academic_session'                => 'required|string|max:50',
            'principal_name'                  => 'nullable|string|max:255',
            'institution_phone'               => 'nullable|string|max:50',
            'institution_email'               => 'nullable|email|max:255',
            'institution_address'             => 'nullable|string|max:500',
            'school_legal_entity_name'        => 'nullable|string|max:255',
            'school_pan'                      => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'brand_primary_color'             => 'nullable|string|max:30',
            // Bank details for display on receipts (no payment processing)
            'bank_name'                       => 'nullable|string|max:100',
            'bank_account_number'             => 'nullable|string|max:50',
            'bank_ifsc'                       => 'nullable|string|max:20',
            'bank_account_holder'             => 'nullable|string|max:150',
            'bank_branch'                     => 'nullable|string|max:100',
            'upi_vpa'                         => 'nullable|string|max:100',
            // Currency
            'currency_code'                   => 'nullable|string|max:10',
            'currency_symbol'                 => 'required|string|max:10',
            'auto_receipt_generate'           => 'nullable|boolean',
            // Attendance Timetable Rules
            'attendance_period_grace_minutes' => 'nullable|integer|min:0|max:120',
        ]);

        // Handle booleans
        $validated['auto_receipt_generate'] = $request->has('auto_receipt_generate') ? '1' : '0';

        $auditedKeys = [
            'institute_name', 'school_short_name', 'campus_name',
            'academic_session', 'board_name', 'affiliation_number',
            'principal_name', 'institution_phone', 'institution_email', 'institution_address',
        ];
        $oldValues = AcademySetting::whereIn('key', $auditedKeys)->pluck('value', 'key')->toArray();
        $newValues = array_intersect_key($validated, array_flip($auditedKeys));

        DB::transaction(function () use ($validated) {
            foreach ($validated as $key => $value) {
                AcademySetting::set($key, $value === null ? null : (string) $value);
            }
        });

        // Remove any stale legacy gateway/GST keys
        AcademySetting::whereIn('key', [
            'active_payment_gateway', 'payment_gateway_mode', 'ownpay_key', 'ownpay_secret',
            'razorpay_enabled', 'razorpay_key_id', 'razorpay_key_secret', 'razorpay_webhook_secret',
            'stripe_enabled', 'cashfree_enabled',
            'gst_registration_status', 'gstin', 'gst_legal_name', 'gst_state_code', 'gst_registration_date',
            'upi_enabled', 'bank_transfer_enabled', 'upi_mcc', 'upi_merchant_name',
        ])->delete();

        try {
            SchoolAdminAudit::create([
                'user_id'     => $user->id,
                'action'      => 'school_settings_updated',
                'description' => 'Updated school profile and institutional configuration.',
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'ip_address'  => $request->ip(),
            ]);
        } catch (\Throwable $e) {}

        SchoolBrandingService::flush();

        return redirect()->route('settings.index')
            ->with('success', 'School profile and settings updated successfully.');
    }

    /**
     * Upload / replace the school payment QR image.
     * This static QR is displayed on fee invoices for parents to scan and pay.
     */
    public function uploadQrImage(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Only administrators can upload the payment QR code.');
        }

        $request->validate([
            'payment_qr_image' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $old = AcademySetting::get('payment_qr_image');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        $path = $request->file('payment_qr_image')->store('school/qr', 'public');
        AcademySetting::set('payment_qr_image', $path);

        return redirect()->back()->with('success', 'Payment QR code updated. It will now appear on all fee invoices.');
    }
}
