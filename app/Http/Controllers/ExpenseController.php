<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\Gym;
use App\Scopes\GymScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * پیشاندانی بەشی بەڕێوەبردنی خەرجییە گشتییەکانی هۆڵ
     */
    public function index(Request $request): View
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;
        $activeGym = Gym::find($activeGymId);

        $query = Expense::where('gym_id', $activeGymId)->with('recorder');

        // فلتەری گەڕان بەپێی ناونیشان یان ناوی وەرگر
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // فلتەری بەش (Category)
        if ($category = $request->input('category')) {
            if ($category !== 'ALL') {
                $query->where('category', $category);
            }
        }

        // فلتەری کات
        $dateFilter = $request->input('date_filter', 'all');
        if ($dateFilter === 'today') {
            $query->whereDate('spent_at', now()->toDateString());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('spent_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'this_month') {
            $query->whereMonth('spent_at', now()->month)->whereYear('spent_at', now()->year);
        }

        // کۆکردنەوەی داتای ئاماری
        $todayExpenses = Expense::where('gym_id', $activeGymId)
            ->whereDate('spent_at', now()->toDateString())
            ->sum('amount');

        $thisMonthExpenses = Expense::where('gym_id', $activeGymId)
            ->whereMonth('spent_at', now()->month)
            ->whereYear('spent_at', now()->year)
            ->sum('amount');

        $totalExpensesAllTime = Expense::where('gym_id', $activeGymId)->sum('amount');
        $totalReceiptsCount = Expense::where('gym_id', $activeGymId)->count();

        $expenses = $query->orderBy('spent_at', 'desc')->orderBy('id', 'desc')->paginate(20)->withQueryString();

        $categories = [
            'rent' => '🏢 کرێی هۆڵ',
            'electricity' => '⚡ مۆلیدە و کارەبا',
            'maintenance' => '🔧 چاککردنەوە و ئامێرەکان',
            'salaries' => '👥 مووچەی کارمەندان و ڕاهێنەران',
            'cleaning' => '🧼 کەلوپەلی پاکوخاوێنی',
            'equipment' => '🏋️‍♂️ کڕینی کەلوپەل و وەرزش',
            'other' => '📦 خەرجییە هەمەجۆرەکان',
        ];

        return view('expenses.index', compact(
            'activeGym',
            'expenses',
            'categories',
            'todayExpenses',
            'thisMonthExpenses',
            'totalExpensesAllTime',
            'totalReceiptsCount'
        ));
    }

    /**
     * تۆمارکردنی خەرجی نوێ بۆ هۆڵ
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'required|string|in:rent,electricity,maintenance,salaries,cleaning,equipment,other',
            'amount' => 'required|numeric|min:1',
            'spent_at' => 'required|date',
            'paid_to' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
        ]);

        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()->id;

        $expense = Expense::create([
            'gym_id' => $activeGymId,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'spent_at' => $validated['spent_at'],
            'paid_to' => $validated['paid_to'] ?? null,
            'recorded_by_user_id' => auth()->id() ?? 1,
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::create([
            'gym_id' => $activeGymId,
            'user_id' => auth()->id() ?? 1,
            'action' => 'expense.created',
            'auditable_type' => Expense::class,
            'auditable_id' => $expense->id,
            'new_values' => [
                'title' => $expense->title,
                'amount' => $expense->amount,
                'category' => $expense->category,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('expenses.index')->with('success', "خەرجی بە بڕی " . number_format($expense->amount) . " IQD بە سەرکەوتوویی تۆمارکرا.");
    }

    /**
     * سڕینەوەی خەرجی
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $title = $expense->title;
        $amount = $expense->amount;

        AuditLog::create([
            'gym_id' => $expense->gym_id,
            'user_id' => auth()->id() ?? 1,
            'action' => 'expense.deleted',
            'auditable_type' => Expense::class,
            'auditable_id' => $expense->id,
            'old_values' => [
                'title' => $title,
                'amount' => $amount,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', "خەرجی '{$title}' بە سەرکەوتوویی سڕایەوە.");
    }
}
