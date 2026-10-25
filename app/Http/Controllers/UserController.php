<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Scopes\GymScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * پیشاندانی لیستی سەرجەم بەکارهێنەرانی سیستەم و ڕۆڵەکانیان
     */
    public function index(Request $request)
    {
        $activeGymId = GymScope::getActiveGymId();
        $search = $request->query('search');
        $roleFilter = $request->query('role');

        $query = User::with(['roles', 'gyms', 'currentGym', 'directPermissions']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where(function ($q) use ($roleFilter) {
                if ($roleFilter === 'super_admin') {
                    $q->where('is_super_admin', true);
                } else {
                    $q->whereHas('roles', fn($r) => $r->where('name', $roleFilter))
                      ->orWhereHas('gyms', fn($g) => $g->where('role_id', Role::where('name', $roleFilter)->value('id')));
                }
            });
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();
        $gyms = Gym::all();

        // ئامارەکان
        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'super_admins' => User::where('is_super_admin', true)->count(),
            'gym_owners' => DB::table('gym_user')->where('role_id', Role::where('name', 'gym_owner')->value('id') ?? 0)->distinct('user_id')->count('user_id'),
            'managers' => DB::table('gym_user')->where('role_id', Role::where('name', 'gym_manager')->value('id') ?? 0)->distinct('user_id')->count('user_id'),
            'trainers' => DB::table('gym_user')->where('role_id', Role::where('name', 'trainer')->value('id') ?? 0)->distinct('user_id')->count('user_id'),
        ];

        return view('users.index', compact('users', 'roles', 'gyms', 'stats', 'search', 'roleFilter'));
    }

    /**
     * تۆمارکردنی بەکارهێنەری نوێ لەگەڵ دیاریکردنی هۆڵ و ڕۆڵ
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:50|unique:users,phone',
            'password' => 'required|string|min:6',
            'gym_id' => 'required|exists:gyms,id',
            'role_id' => 'required|exists:roles,id',
            'is_super_admin' => 'nullable|boolean',
        ], [
            'name.required' => 'تکایە ناوی تەواوی بەکارهێنەر بنووسە.',
            'email.required' => 'ئیمەیڵ پێویستە.',
            'email.unique' => 'ئەم ئیمەیڵە پێشتر بەکارهاتووە.',
            'phone.unique' => 'ئەم ژمارە مۆبایلە پێشتر بەکارهاتووە.',
            'password.min' => 'وشەی نهێنی نابێت لە ٦ پیت کەمتر بێت.',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'is_super_admin' => $request->boolean('is_super_admin'),
                'is_active' => true,
                'current_gym_id' => $request->gym_id,
            ]);

            $role = Role::findOrFail($request->role_id);

            // پەیوەستکردنی بە هۆڵەکەیەوە
            $user->gyms()->attach($request->gym_id, [
                'role_id' => $role->id,
                'is_active' => true,
            ]);

            // ئەگەر ڕۆڵی گشتی بوو
            $user->roles()->attach($role->id);
        });

        return redirect()->route('users.index')->with('success', 'بەکارهێنەری نوێ بە سەرکەوتوویی زیادکرا.');
    }

    /**
     * فۆرمی تایبەتی دەستکاریکردنی بەکارهێنەر و مۆڵەتەکان (User & Permissions Edit Form)
     */
    public function edit(User $user)
    {
        $roles = Role::with('permissions')->get();
        $gyms = Gym::all();

        // هەموو مۆڵەتەکان بە کۆمەڵەکراوی (Grouped Permissions)
        $permissions = Permission::all()->groupBy('group_name');

        // ڕۆڵی بەکارهێنەر لە هۆڵی ئێستای
        $activeGymId = $user->current_gym_id ?? GymScope::getActiveGymId() ?? Gym::first()->id;
        $currentRole = $user->getRoleInGym($activeGymId) ?? $user->roles()->first();

        // ئەو مۆڵەتانەی کە لە ڕێگەی ڕۆڵەکەیەوە هەیەتی
        $rolePermissionIds = $currentRole ? $currentRole->permissions()->pluck('permissions.id')->toArray() : [];

        // ئەو مۆڵەتە تایبەتانەی کە ڕاستەوخۆ بەخشراون پێی
        $directPermissionIds = $user->directPermissions()->pluck('permissions.id')->toArray();

        return view('users.edit', compact(
            'user',
            'roles',
            'gyms',
            'permissions',
            'currentRole',
            'rolePermissionIds',
            'directPermissionIds',
            'activeGymId'
        ));
    }

    /**
     * نوێکردنەوەی زانیاری بەکارهێنەر، ڕۆڵ و گۆڕانکاری لە مۆڵەتەکان (Permissions Update)
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'gym_id' => 'required|exists:gyms,id',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'nullable|boolean',
            'is_super_admin' => 'nullable|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::transaction(function () use ($request, $user) {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'current_gym_id' => $request->gym_id,
                'is_active' => $request->boolean('is_active'),
            ];

            // تەنها ئەگەر سوپەر ئەدمین بێت دەتوانێت دەسەڵاتی سوپەر ئەدمین ببەخشێت
            if (auth()->user()?->is_super_admin) {
                $data['is_super_admin'] = $request->boolean('is_super_admin');
            }

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $user->update($data);

            $roleId = $request->role_id;
            $gymId = $request->gym_id;

            // نوێکردنەوە یان دروستکردنی پەیوەندی لە gym_user
            DB::table('gym_user')->updateOrInsert(
                ['user_id' => $user->id, 'gym_id' => $gymId],
                ['role_id' => $roleId, 'is_active' => true, 'updated_at' => now()]
            );

            // نوێکردنەوەی ڕۆڵ لە role_user
            $user->roles()->sync([$roleId]);

            // نوێکردنەوەی مۆڵەتە ڕاستەوخۆکان (Direct Permissions Sync)
            $selectedPermissions = $request->input('permissions', []);
            $user->directPermissions()->sync($selectedPermissions);
        });

        return redirect()->route('users.index')->with('success', "مۆڵەت و زانیارییەکانی بەکارهێنەر ({$user->name}) بە سەرکەوتوویی نوێکرایەوە.");
    }

    /**
     * گۆڕینی دۆخی بەکارهێنەر (چالاک / ناچالاک)
     */
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ناتوانیت هەژماری خۆت ناچالاک بکەیت!');
        }

        $user->update(['is_active' => !$user->is_active]);
        $statusText = $user->is_active ? 'چالاککرا' : 'ناچالاککرا';

        return back()->with('success', "بەکارهێنەر {$user->name} بە سەرکەوتوویی {$statusText}.");
    }

    /**
     * سڕینەوەی بەکارهێنەر
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ناتوانیت هەژمارەکەی خۆت بسڕیتەوە!');
        }

        if ($user->is_super_admin && User::where('is_super_admin', true)->count() <= 1) {
            return back()->with('error', 'ناتوانیت تاکە بەڕێوەبەری سەرەکی پلاتفۆرم بسڕیتەوە!');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "بەکارهێنەر ({$name}) بە سەرکەوتوویی سڕایەوە.");
    }
}
