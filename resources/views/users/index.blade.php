@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    بەڕێوەبردنی دەسەڵاتەکان (RBAC)
                </span>
            </div>
            <h1 class="text-2xl font-black text-white mt-1 flex items-center gap-2">
                <span>👥 بەکارهێنەران، ڕۆڵەکان و مۆڵەتەکانی سیستەم</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                زیادکردنی کارمەند، خاوەنی هۆڵ، بەڕێوەبەر، ژمێریار، ڕاهێنەر و کاشێر لەگەڵ دیاریکردنی وردی دەسەڵاتەکان
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button onclick="openNewUserModal()" class="bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 text-white font-bold text-xs px-4 py-2.5 rounded-2xl shadow-lg shadow-emerald-600/20 transition flex items-center gap-1.5">
                <span>➕ زیادکردنی بەکارهێنەری نوێ</span>
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[11px] text-slate-400 font-bold block">سەرجەم بەکارهێنەران</span>
            <span class="text-xl font-black text-white mt-1 block">{{ $stats['total'] }}</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[11px] text-emerald-400 font-bold block">چالاکەکان</span>
            <span class="text-xl font-black text-emerald-400 mt-1 block">{{ $stats['active'] }}</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[11px] text-indigo-400 font-bold block">Super Admin</span>
            <span class="text-xl font-black text-indigo-300 mt-1 block">{{ $stats['super_admins'] }}</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[11px] text-teal-400 font-bold block">خاوەنی هۆڵ</span>
            <span class="text-xl font-black text-teal-300 mt-1 block">{{ $stats['gym_owners'] }}</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[11px] text-amber-400 font-bold block">بەڕێوەبەر</span>
            <span class="text-xl font-black text-amber-300 mt-1 block">{{ $stats['managers'] }}</span>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-md">
            <span class="text-[11px] text-rose-400 font-bold block">ڕاهێنەر (Trainer)</span>
            <span class="text-xl font-black text-rose-300 mt-1 block">{{ $stats['trainers'] }}</span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-md flex flex-col sm:flex-row justify-between items-center gap-3">
        <form action="{{ route('users.index') }}" method="GET" class="w-full sm:w-auto flex-1 flex flex-col sm:flex-row gap-2">
            <input type="text" 
                   name="search" 
                   value="{{ $search }}" 
                   placeholder="گەڕان بەپێی ناو، ئیمەیڵ، یان ژمارەی مۆبایل..." 
                   class="w-full sm:max-w-xs bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-sans">
            
            <select name="role" onchange="this.form.submit()" class="bg-slate-800 border border-slate-700 text-slate-300 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-indigo-500 font-sans">
                <option value="">-- هەموو ڕۆڵەکان --</option>
                <option value="super_admin" {{ $roleFilter === 'super_admin' ? 'selected' : '' }}>👑 Super Admin</option>
                @foreach($roles as $r)
                    <option value="{{ $r->name }}" {{ $roleFilter === $r->name ? 'selected' : '' }}>
                        {{ $r->display_name_ku }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-750 text-white rounded-xl text-xs font-bold transition">
                فلتەر
            </button>
            @if($search || $roleFilter)
                <a href="{{ route('users.index') }}" class="px-3 py-2 bg-slate-850 hover:bg-slate-800 text-slate-400 rounded-xl text-xs transition flex items-center justify-center">
                    سڕینەوەی فلتەر
                </a>
            @endif
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-2xl">
        <div class="p-4 border-b border-slate-800 flex justify-between items-center">
            <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>📋 لیستی هەژمارە تۆمارکراوەکان</span>
            </h2>
            <span class="text-xs text-slate-500 font-mono">کۆی گشتی: {{ $users->total() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-slate-850/80 text-slate-400 border-b border-slate-800">
                        <th class="p-4 font-bold">بەکارهێنەر</th>
                        <th class="p-4 font-bold">پەیوەندی (ئیمەیڵ / مۆبایل)</th>
                        <th class="p-4 font-bold">هۆڵی کارکردن</th>
                        <th class="p-4 font-bold">ڕۆڵی سەرەکی</th>
                        <th class="p-4 font-bold">مۆڵەتە تایبەتەکان</th>
                        <th class="p-4 font-bold">دۆخ</th>
                        <th class="p-4 font-bold text-center">کردارەکان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($users as $u)
                        @php
                            $role = $u->primary_role;
                            $roleColor = match($role?->name ?? '') {
                                'super_admin' => 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
                                'gym_owner' => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
                                'gym_manager' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                'accountant' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                'trainer' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                'shop_cashier' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/30',
                                default => 'bg-slate-800 text-slate-300 border-slate-700'
                            };
                            $directCount = $u->directPermissions->count();
                        @endphp
                        <tr class="hover:bg-slate-850/40 transition">
                            <!-- Name & Avatar -->
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-white text-xs">
                                        {{ mb_substr($u->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-white block text-sm">{{ $u->name }}</span>
                                        @if($u->is_super_admin)
                                            <span class="text-[10px] text-indigo-400 font-bold block">Super Administrator</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Email / Phone -->
                            <td class="p-4 font-mono">
                                <span class="text-slate-300 block">{{ $u->email }}</span>
                                <span class="text-slate-500 text-[11px]">{{ $u->phone ?? 'بێ مۆبایل' }}</span>
                            </td>

                            <!-- Gym -->
                            <td class="p-4 text-slate-300">
                                @if($u->currentGym)
                                    <span class="font-medium text-white flex items-center gap-1">
                                        <span>🏛️</span>
                                        <span>{{ $u->currentGym->name }}</span>
                                    </span>
                                @else
                                    <span class="text-slate-500 italic">هەموو هۆڵەکان (گشتی)</span>
                                @endif
                            </td>

                            <!-- Role Badge -->
                            <td class="p-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold border {{ $roleColor }}">
                                    {{ $role?->display_name_ku ?? ($u->is_super_admin ? 'بەڕێوەبەری سەرەکی' : 'بێ ڕۆڵ') }}
                                </span>
                            </td>

                            <!-- Direct Permissions Badge -->
                            <td class="p-4">
                                @if($directCount > 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-xs font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">
                                        <span>✨</span>
                                        <span>{{ $directCount }} مۆڵەتی تایبەت</span>
                                    </span>
                                @else
                                    <span class="text-slate-500 text-[11px]">بەپێی ڕۆڵ</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="p-4">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        چالاکە
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        ناچالاک
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Edit Permissions & Profile Button -->
                                    <a href="{{ route('users.edit', $u->id) }}" 
                                       class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600/80 hover:bg-indigo-600 text-white transition flex items-center gap-1 shadow">
                                        <span>⚙️ دەستکاری مۆڵەتەکان</span>
                                    </a>

                                    <!-- Toggle Active Status -->
                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                    title="{{ $u->is_active ? 'ناچالاککردن' : 'چالاککردن' }}" 
                                                    class="p-1.5 rounded-xl text-xs font-bold {{ $u->is_active ? 'bg-slate-800 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400' : 'bg-emerald-500/20 text-emerald-300' }} border border-slate-700 transition">
                                                {{ $u->is_active ? '⏸️' : '▶️' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                هیچ بەکارهێنەرێک نەدۆزرایەوە.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-800">
            {{ $users->links() }}
        </div>
    </div>

</div>

<!-- Modal: زیادکردنی بەکارهێنەری نوێ (Add User Modal) -->
<div id="new-user-modal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
        <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>➕ زیادکردنی بەکارهێنەری نوێ</span>
            </h3>
            <button onclick="closeNewUserModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">ناوی تەواوی بەکارهێنەر *</label>
                <input type="text" name="name" required placeholder="وەک: کاروان محەمەد" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ئیمەیڵ *</label>
                    <input type="email" name="email" required placeholder="user@gympro.test" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500 font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ژمارەی مۆبایل</label>
                    <input type="text" name="phone" placeholder="0750xxxxxxx" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">هۆڵی کارکردن *</label>
                    <select name="gym_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
                        @foreach($gyms as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">ڕۆڵی سەرەکی *</label>
                    <select name="role_id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500">
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->display_name_ku }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">وشەی نهێنی (Password) *</label>
                <input type="password" name="password" required placeholder="نابێت لە ٦ پیت کەمتر بێت..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-emerald-500 font-mono">
            </div>

            @if(auth()->user()?->is_super_admin)
                <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-indigo-300 select-none">
                        <input type="checkbox" name="is_super_admin" value="1" class="rounded bg-slate-800 border-slate-700 text-indigo-500 focus:ring-0">
                        <span>پێدانی دەسەڵاتی Super Admin (دەسەڵاتی تەواوی سیستەم)</span>
                    </label>
                </div>
            @endif

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeNewUserModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-700">
                    پەشیمانبوونەوە
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/30">
                    تۆمارکردن
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openNewUserModal() {
        document.getElementById('new-user-modal').classList.remove('hidden');
    }

    function closeNewUserModal() {
        document.getElementById('new-user-modal').classList.add('hidden');
    }
</script>
@endpush
