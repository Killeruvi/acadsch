<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\FeePayment;
use App\Models\SchoolExpense;
use App\Models\SchoolIncome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SchoolAccountsController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Only administrators may access institutional financial records.');
        }

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        if ($from && $to && $to < $from) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'to' => 'The end date must be on or after the start date.',
            ]);
        }

        $incomesQuery = SchoolIncome::with('receiver')->latest('date');
        $expensesQuery = SchoolExpense::with('approver')->latest('date');
        $feePaymentsQuery = FeePayment::settled();
        $refundsQuery = FeePayment::where('status', 'refunded');

        if ($from) {
            $incomesQuery->whereDate('date', '>=', $from);
            $expensesQuery->whereDate('date', '>=', $from);
            $feePaymentsQuery->whereDate('paid_at', '>=', $from);
            $refundsQuery->whereDate('refunded_at', '>=', $from);
        }
        if ($to) {
            $incomesQuery->whereDate('date', '<=', $to);
            $expensesQuery->whereDate('date', '<=', $to);
            $feePaymentsQuery->whereDate('paid_at', '<=', $to);
            $refundsQuery->whereDate('refunded_at', '<=', $to);
        }

        // Calculate period totals before pagination mutates the ledger query builders.
        $grossFeeCollections = (float) (clone $feePaymentsQuery)->sum('amount');
        $feeRefunds = (float) (clone $refundsQuery)->sum('amount');
        $netFeeCollections = round($grossFeeCollections - $feeRefunds, 2);
        $totalOtherIncome = (float) (clone $incomesQuery)->sum('amount');
        $totalExpenses = (float) (clone $expensesQuery)->sum('amount');
        $grossIncome = $netFeeCollections + $totalOtherIncome;

        $incomes = $incomesQuery->paginate(10, ['*'], 'income_page')->withQueryString();
        $expenses = $expensesQuery->paginate(10, ['*'], 'expense_page')->withQueryString();

        $stats = [
            'gross_income' => $grossIncome,
            'fee_collections' => $netFeeCollections,
            'gross_fee_collections' => $grossFeeCollections,
            'fee_refunds' => $feeRefunds,
            'other_income' => $totalOtherIncome,
            'total_expenses' => $totalExpenses,
            'net_balance' => $grossIncome - $totalExpenses,
        ];

        return view('school.accounts.index', compact('incomes', 'expenses', 'stats', 'from', 'to'));
    }

    public function storeIncome(Request $request): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'income_head' => 'required|string|max:60',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:40',
            'invoice_number' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:255',
        ]);

        $validated['received_by'] = Auth::id();

        SchoolIncome::create($validated);

        return redirect()->back()->with('success', "Income entry of ₹" . number_format($validated['amount'], 2) . " logged successfully!");
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'expense_head' => 'required|string|max:60',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_method' => 'required|string|max:40',
            'voucher_number' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:255',
            'paid_to' => 'nullable|string|max:150',
        ]);

        $validated['approved_by'] = Auth::id();

        SchoolExpense::create($validated);

        return redirect()->back()->with('success', "Expense voucher of ₹" . number_format($validated['amount'], 2) . " recorded!");
    }
}
