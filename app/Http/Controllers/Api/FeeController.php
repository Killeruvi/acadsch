<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FeeController extends Controller
{
    /**
     * List fee invoices for the authenticated user (or all if admin).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = FeeInvoice::with(['course:id,title,code', 'payments']);

        if ($user->isStudent()) {
            $query->where('student_id', $user->id);
        } elseif ($user->isParent()) {
            $childIds = User::childrenOf($user)->select('id');
            $query->whereIn('student_id', $childIds);
        } elseif ($user->isAdmin()) {
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->query('student_id'));
            }
        } else {
            abort(403, 'You are not authorized to view fee invoices.');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $perPage = max(1, min(100, $request->integer('per_page', 15)));
        $invoices = $query->latest('due_date')->paginate($perPage)->withQueryString();

        return response()->json($invoices);
    }

    /**
     * Process an online fee payment for an invoice.
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        // A client request is not proof that a payment succeeded.
        // This endpoint is therefore limited to authenticated administrators
        // recording offline cash/bank payments; payments are settled via manual cashier QR verification.
        abort_unless($request->user() && $request->user()->isAdmin(), 403,
            'Only administrators may record offline fee payments through this endpoint.');

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:Cash,Bank Transfer',
        ]);

        $result = DB::transaction(function () use ($id, $validated) {
            $invoice = FeeInvoice::whereKey($id)->lockForUpdate()->firstOrFail();
            $balance = (float) $invoice->balance;
            $payAmount = (float) $validated['amount'];
            if ($balance <= 0) {
                abort(400, 'Invoice is already settled in full.');
            }
            if ($payAmount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => ['The payment amount cannot exceed the current invoice balance.'],
                ]);
            }

            $newPaidTotal = (float) $invoice->paid_amount + $payAmount;
            $status = $newPaidTotal >= (float) $invoice->total_due ? 'paid' : 'partial';
            $txCount = FeePayment::count() + 1;
            $txnRef = 'TXN-OFF-'.strtoupper(Str::random(8)).'-'.$txCount;
            $receiptNum = 'REC-'.date('Y').'-'.str_pad((string) $txCount, 4, '0', STR_PAD_LEFT);

            $payment = FeePayment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'transaction_reference' => $txnRef,
                'payment_method' => $validated['payment_method'],
                'amount' => $payAmount,
                'paid_at' => now(),
                'status' => 'successful',
                'receipt_number' => $receiptNum,
                'notes' => 'Offline payment recorded by an administrator through the API.',
            ]);

            $invoice->update(['paid_amount' => $newPaidTotal, 'status' => $status]);

            return [$payment, $invoice->fresh()];
        });

        return response()->json([
            'message' => 'Offline payment recorded successfully',
            'payment' => $result[0],
            'invoice' => $result[1],
        ], 201);
    }
}
