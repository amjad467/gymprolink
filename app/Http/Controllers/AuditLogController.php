<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Gym;
use App\Models\User;
use App\Scopes\GymScope;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * پیشاندانی لۆگی تەواوەتی چاودێری و کردارە هەستیارەکان (Audit Log Dashboard)
     */
    public function index(Request $request)
    {
        $activeGymId = GymScope::getActiveGymId() ?? Gym::first()?->id;

        $query = AuditLog::with(['user', 'gym'])->latest();

        // فلتەر بەپێی هۆڵ ئەگەر سوپەر ئەدمین نەبێت
        if (!auth()->user()?->is_super_admin && $activeGymId) {
            $query->where(function ($q) use ($activeGymId) {
                $q->where('gym_id', $activeGymId)->orWhereNull('gym_id');
            });
        }

        // فلتەر بەپێی پۆلێنکردنی کردار (Action Category)
        if ($category = $request->get('category')) {
            if ($category === 'auth') {
                $query->where('action', 'like', 'auth.%');
            } elseif ($category === 'finance') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%EXPENSE%')
                      ->orWhere('action', 'like', '%PAYMENT%')
                      ->orWhere('action', 'like', '%DEBT%')
                      ->orWhere('action', 'like', '%CLOSING%');
                });
            } elseif ($category === 'security') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%RESTORE%')
                      ->orWhere('action', 'like', '%LOCKED%')
                      ->orWhere('action', 'like', '%BACKUP%')
                      ->orWhere('action', 'like', '%PERMISSION%');
                });
            } elseif ($category === 'members') {
                $query->where(function ($q) {
                    $q->where('action', 'like', '%MEMBER%')
                      ->orWhere('action', 'like', '%FREEZE%')
                      ->orWhere('action', 'like', '%RENEW%');
                });
            }
        }

        // فلتەر بەپێی بەکارهێنەر
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        // گەڕان بە دەق (Search)
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // ئاماری خێرا (KPI Stats)
        $today = now()->startOfDay();
        $stats = [
            'total_today' => (clone $query)->where('created_at', '>=', $today)->count(),
            'auth_events' => (clone $query)->where('action', 'like', 'auth.%')->count(),
            'financial_events' => (clone $query)->where(function ($q) {
                $q->where('action', 'like', '%EXPENSE%')
                  ->orWhere('action', 'like', '%PAYMENT%')
                  ->orWhere('action', 'like', '%DEBT%');
            })->count(),
            'security_events' => (clone $query)->where(function ($q) {
                $q->where('action', 'like', '%RESTORE%')
                  ->orWhere('action', 'like', '%LOCKED%')
                  ->orWhere('action', 'like', '%BACKUP%');
            })->count(),
        ];

        $logs = $query->paginate(25)->withQueryString();
        $users = User::all(['id', 'name', 'email']);

        return view('audit_logs.index', compact('logs', 'stats', 'users', 'category', 'search'));
    }
}
