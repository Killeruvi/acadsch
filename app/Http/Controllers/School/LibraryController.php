<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicSubject;
use App\Models\BookIssue;
use App\Models\BookReservation;
use App\Models\LibraryBook;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibraryController extends Controller
{
    private function canManage(): bool
    {
        return Auth::check() && (Auth::user()->isAdmin() || Auth::user()->isTeacher());
    }

    public function index(Request $request): View
    {
        $user = Auth::user();
        $search = trim((string) $request->input('search', ''));
        $subjectId = $request->input('subject_id');
        $status = $request->input('status', 'active');

        $booksQuery = LibraryBook::query()->with('subject')->orderBy('book_title');
        if ($search !== '') {
            $booksQuery->where(fn (Builder $q) => $q->where('book_title', 'like', "%{$search}%")
                ->orWhere('book_number', 'like', "%{$search}%")
                ->orWhere('author_name', 'like', "%{$search}%")
                ->orWhere('isbn_number', 'like', "%{$search}%"));
        }
        if ($subjectId !== null && $subjectId !== '') {
            $booksQuery->where('subject_id', $subjectId);
        }
        $books = $booksQuery->paginate(10, ['*'], 'books_page')->withQueryString();

        $issuesQuery = BookIssue::with(['book', 'member'])->latest('issue_date');
        if ($user->isStudent()) {
            $issuesQuery->where('member_id', $user->id);
        } elseif ($user->role === 'parent') {
            $childIds = $user->children()->pluck('id');
            $issuesQuery->whereIn('member_id', $childIds);
        } elseif (! $this->canManage()) {
            $issuesQuery->whereRaw('1 = 0');
        }
        if (in_array($status, ['issued', 'returned', 'overdue'], true)) {
            if ($status === 'overdue') {
                $issuesQuery->where('status', 'issued')->whereDate('due_date', '<', today());
            } else {
                $issuesQuery->where('status', $status);
            }
        }
        $issues = $issuesQuery->paginate(10, ['*'], 'issues_page')->withQueryString();

        $subjects = AcademicSubject::orderBy('name')->get();
        $members = $this->canManage()
            ? User::whereIn('role', ['student', 'teacher'])->orderBy('name')->get()
            : collect();
        $stats = [
            'total_books' => (int) LibraryBook::sum('quantity'),
            'available_books' => (int) LibraryBook::sum('available_quantity'),
            'issued_books' => (int) BookIssue::where('status', 'issued')->count(),
            'overdue_books' => (int) BookIssue::where('status', 'issued')->whereDate('due_date', '<', today())->count(),
            'total_titles' => (int) LibraryBook::count(),
        ];

        return view('school.library.index', compact('books', 'issues', 'subjects', 'members', 'stats', 'search', 'subjectId', 'status'));
    }

    public function storeBook(Request $request): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        $validated = $request->validate([
            'book_title' => ['required', 'string', 'max:200'],
            'book_number' => ['required', 'string', 'max:60', 'unique:library_books,book_number'],
            'isbn_number' => ['nullable', 'string', 'max:60'],
            'publisher_name' => ['nullable', 'string', 'max:150'],
            'author_name' => ['nullable', 'string', 'max:150'],
            'subject_id' => ['nullable', 'exists:academic_subjects,id'],
            'rack_number' => ['nullable', 'string', 'max:60'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'book_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $validated['available_quantity'] = $validated['quantity'];
        $validated['book_price'] = $validated['book_price'] ?? 0;
        LibraryBook::create($validated);

        return back()->with('success', 'Book catalogued successfully.');
    }

    public function issueBook(Request $request): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        $validated = $request->validate([
            'book_id' => ['required', 'integer', 'exists:library_books,id'],
            'member_id' => ['required', 'integer', Rule::exists('users', 'id')->whereIn('role', ['student', 'teacher'])],
            'due_date' => ['required', 'date_format:Y-m-d', 'after:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $issue = DB::transaction(function () use ($validated) {
            $book = LibraryBook::whereKey($validated['book_id'])->lockForUpdate()->firstOrFail();
            $member = User::whereKey($validated['member_id'])->lockForUpdate()->firstOrFail();
            $activeCount = BookIssue::where('member_id', $member->id)->where('status', 'issued')->count();
            if ($activeCount >= (int) config('library.max_active_issues', 5)) {
                throw ValidationException::withMessages(['member_id' => 'This member has reached the active borrowing limit.']);
            }
            if ($book->available_quantity < 1) {
                throw ValidationException::withMessages(['book_id' => 'No available copies remain for this title.']);
            }
            $nextReservation = BookReservation::where('book_id', $book->id)->where('status', 'pending')->orderBy('reserved_at')->orderBy('id')->lockForUpdate()->first();
            if ($nextReservation && (int) $nextReservation->member_id !== (int) $member->id) {
                throw ValidationException::withMessages(['member_id' => 'A reservation queue exists. Issue this copy to the earliest reserver first.']);
            }
            if ($nextReservation) {
                $nextReservation->update(['status' => 'fulfilled', 'fulfilled_at' => now()]);
            }
            $book->decrement('available_quantity');

            return BookIssue::create([
                'book_id' => $book->id,
                'member_id' => $member->id,
                'issue_date' => today(),
                'due_date' => $validated['due_date'],
                'status' => 'issued',
                'fine_amount' => 0,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return back()->with('success', 'Book issued successfully. Issue reference: #'.$issue->id);
    }

    public function returnBook(Request $request, int $issueId): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:255']]);
        DB::transaction(function () use ($issueId, $validated) {
            $issue = BookIssue::whereKey($issueId)->lockForUpdate()->firstOrFail();
            if ($issue->status !== 'issued') {
                throw ValidationException::withMessages(['issue' => 'Only currently issued books can be returned.']);
            }
            $book = LibraryBook::whereKey($issue->book_id)->lockForUpdate()->firstOrFail();
            if ($book->available_quantity >= $book->quantity) {
                throw new \RuntimeException('Library inventory is inconsistent; return was stopped.');
            }
            $issue->update(['status' => 'returned', 'return_date' => today(), 'fine_amount' => 0, 'note' => $validated['note'] ?? $issue->note]);
            $book->increment('available_quantity');
        });

        return back()->with('success', 'Book return recorded. No fine has been charged.');
    }

    public function renewBook(Request $request, int $issueId): RedirectResponse
    {
        abort_unless($this->canManage(), 403);
        $validated = $request->validate(['due_date' => ['required', 'date_format:Y-m-d', 'after:today']]);
        DB::transaction(function () use ($issueId, $validated) {
            $issue = BookIssue::whereKey($issueId)->lockForUpdate()->firstOrFail();
            if ($issue->status !== 'issued') {
                throw ValidationException::withMessages(['issue' => 'Only active issues can be renewed.']);
            }
            $maxRenewals = (int) config('library.max_renewals', 2);
            if ((int) $issue->renewal_count >= $maxRenewals) {
                throw ValidationException::withMessages(['due_date' => 'The renewal limit has been reached.']);
            }
            if ($validated['due_date'] <= $issue->due_date->toDateString()) {
                throw ValidationException::withMessages(['due_date' => 'The new due date must be later than the current due date.']);
            }
            $issue->update(['due_date' => $validated['due_date'], 'renewal_count' => (int) $issue->renewal_count + 1]);
        });

        return back()->with('success', 'Loan renewed successfully.');
    }

    public function reserveBook(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isStudent() || $user->role === 'teacher'), 403);
        $validated = $request->validate(['book_id' => ['required', 'integer', 'exists:library_books,id']]);
        $reservation = DB::transaction(function () use ($validated, $user) {
            $book = LibraryBook::whereKey($validated['book_id'])->lockForUpdate()->firstOrFail();
            if ($book->available_quantity > 0) {
                throw ValidationException::withMessages(['book_id' => 'Copies are currently available; ask the library to issue one directly.']);
            }
            $existing = BookReservation::where('book_id', $book->id)->where('member_id', $user->id)->where('status', 'pending')->exists();
            if ($existing) {
                throw ValidationException::withMessages(['book_id' => 'An active reservation already exists for this title.']);
            }

            return BookReservation::create(['book_id' => $book->id, 'member_id' => $user->id, 'status' => 'pending', 'reserved_at' => now()]);
        });

        return back()->with('success', 'Reservation placed. Reference: #'.$reservation->id);
    }

    /**
     * Rapid Barcode Circulation Scanner Terminal.
     */
    public function scannerView(): \Illuminate\View\View
    {
        abort_unless($this->canManage(), 403);
        $recentTransactions = BookIssue::with(['book', 'member'])
            ->latest('updated_at')
            ->take(15)
            ->get();

        return view('school.library.scanner', compact('recentTransactions'));
    }

    /**
     * AJAX endpoint for 1-click barcode issue and return.
     */
    public function quickScan(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless($this->canManage(), 403);

        $barcode = trim((string) $request->input('barcode'));
        $memberBarcode = trim((string) $request->input('member_barcode'));

        if (empty($barcode)) {
            return response()->json(['success' => false, 'message' => 'Please scan or enter a book barcode / accession number.'], 422);
        }

        // Find Book by book_number or isbn_number or ID
        $book = LibraryBook::where('book_number', $barcode)
            ->orWhere('isbn_number', $barcode)
            ->orWhere('id', is_numeric($barcode) ? (int) $barcode : 0)
            ->first();

        if (! $book) {
            return response()->json(['success' => false, 'message' => "Book with barcode '{$barcode}' not found in catalog."], 404);
        }

        // Check if book has an active issued copy
        $activeIssue = BookIssue::where('book_id', $book->id)
            ->where('status', 'issued')
            ->with('member')
            ->first();

        // 1. IF CURRENTLY ISSUED -> INSTANT RETURN
        if ($activeIssue) {
            DB::transaction(function () use ($activeIssue, $book) {
                $activeIssue->update([
                    'status' => 'returned',
                    'return_date' => today(),
                    'fine_amount' => 0,
                    'note' => 'Processed via rapid barcode scanner.',
                ]);
                $book->increment('available_quantity');
            });

            return response()->json([
                'success' => true,
                'action' => 'return',
                'message' => "RETURN SUCCESS: '{$book->book_title}' successfully returned from {$activeIssue->member?->name}.",
                'book_title' => $book->book_title,
                'member_name' => $activeIssue->member?->name,
                'available_quantity' => $book->available_quantity + 1,
            ]);
        }

        // 2. IF AVAILABLE -> ISSUE TO MEMBER
        if (empty($memberBarcode)) {
            return response()->json([
                'success' => true,
                'action' => 'awaiting_member',
                'message' => "Book identified: '{$book->book_title}' (Available: {$book->available_quantity}). Now scan student ID card or staff badge to complete issue.",
                'book_id' => $book->id,
                'book_title' => $book->book_title,
            ]);
        }

        $member = User::where('admission_number', $memberBarcode)
            ->orWhere('biometric_pin', $memberBarcode)
            ->orWhere('id', is_numeric($memberBarcode) ? (int) $memberBarcode : 0)
            ->first();

        if (! $member) {
            return response()->json(['success' => false, 'message' => "Member with ID card barcode '{$memberBarcode}' not found."], 404);
        }

        if ($book->available_quantity < 1) {
            return response()->json(['success' => false, 'message' => "No copies available for '{$book->book_title}'."], 422);
        }

        $issue = DB::transaction(function () use ($book, $member) {
            $createdIssue = BookIssue::create([
                'book_id' => $book->id,
                'member_id' => $member->id,
                'issue_date' => today(),
                'due_date' => today()->addDays(14),
                'status' => 'issued',
                'fine_amount' => 0,
                'note' => 'Issued via rapid barcode scanner.',
            ]);
            $book->decrement('available_quantity');

            return $createdIssue;
        });

        return response()->json([
            'success' => true,
            'action' => 'issue',
            'message' => "ISSUE SUCCESS: '{$book->book_title}' issued to {$member->name} (Due: ".today()->addDays(14)->format('M d, Y').').',
            'book_title' => $book->book_title,
            'member_name' => $member->name,
            'due_date' => today()->addDays(14)->format('M d, Y'),
        ]);
    }
}
