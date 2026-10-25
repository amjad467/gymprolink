@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header & Navigation -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('users.index') }}" class="text-xs text-indigo-400 hover:text-indigo-300 font-bold transition flex items-center gap-1">
                    <span>← گەڕانەوە بۆ لیستی بەکارهێنەران</span>
                </a>
                <span class="text-slate-600">/</span>
                <span class="text-xs text-slate-400">دەستکاریکردنی مۆڵەتەکان</span>
            </div>
            <h1 class="text-2xl font-black text-white mt-1 flex items-center gap-2">
                <span>⚙️ دەستکاریکردنی دەسەڵات و مۆڵەتەکانی: {{ $user->name }}</span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                لە ڕێگەی ئەم فۆرمەوە دەتوانیت ڕۆڵی سەرەکی بگۆڕیت و مۆڵەتی تایبەت زیاد بکەیت یان لێی بسەنیتەوە
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-mono text-slate-400 bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-700">
                USER #{{ $user->id }}
            </span>
        </div>
    </div>

    <!-- Main Edit Form -->
    <form action="{{ route('users.update', $user->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- User Information & Role Selection (Top Card) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-5">
            <h2 class="text-base font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                <span>👤 زانیارییە سەرەکییەکان و ڕۆڵی بەکارهێنەر</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">ناوی تەواو *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-sans">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">ئیمەیڵ *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">ژمارەی مۆبایل</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">هۆڵی کارکردن (Gym Assignment) *</label>
                    <select name="gym_id" required class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-sans">
                        @foreach($gyms as $g)
                            <option value="{{ $g->id }}" {{ (old('gym_id', $user->current_gym_id) == $g->id) ? 'selected' : '' }}>
                                🏛️ {{ $g->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">ڕۆڵی بنەڕەتی (Primary Role) *</label>
                    <select name="role_id" id="role-select" required onchange="handleRoleChange()" class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500 font-sans">
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" 
                                    data-role-name="{{ $r->name }}" 
                                    data-permissions="{{ json_encode($r->permissions->pluck('id')) }}"
                                    {{ ($currentRole?->id == $r->id) ? 'selected' : '' }}>
                                {{ $r->display_name_ku }} ({{ $r->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">وشەی نهێنی نوێ (ئارەزوومەندانە)</label>
                    <input type="password" name="password" placeholder="تەنها لە کاتی گۆڕین لێرە بنووسە..." class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 font-mono">
                </div>
            </div>

            <!-- Toggles for Status and Super Admin -->
            <div class="flex flex-wrap items-center gap-6 pt-3 border-t border-slate-800 text-xs">
                <label class="flex items-center gap-2 cursor-pointer select-none text-slate-300">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-0">
                    <span class="font-bold">هەژمار چالاک بێت (Active User)</span>
                </label>

                @if(auth()->user()?->is_super_admin)
                    <label class="flex items-center gap-2 cursor-pointer select-none text-indigo-300 bg-indigo-500/10 px-3 py-1 rounded-xl border border-indigo-500/20">
                        <input type="checkbox" name="is_super_admin" value="1" {{ old('is_super_admin', $user->is_super_admin) ? 'checked' : '' }} class="w-4 h-4 rounded bg-slate-800 border-slate-700 text-indigo-500 focus:ring-0">
                        <span class="font-bold">👑 پێدانی دەسەڵاتی Super Admin (سەرجەم هۆڵەکان)</span>
                    </label>
                @endif
            </div>
        </div>

        <!-- Permissions Customizer Section (فۆرمی تایبەتی دەستکاریکردنی مۆڵەتەکان) -->
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🛡️ فۆرمی تایبەتی مۆڵەتەکان (Permissions Customizer)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        ئەو مۆڵەتانەی کە دیارییان دەکەیت ڕاستەوخۆ دەبەخشرێن بەم بەکارهێنەرە، تەنانەت ئەگەر لە ڕۆڵەکەشیدا نەبێت
                    </p>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <button type="button" onclick="checkAllPermissions(true)" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-200 border border-slate-700 transition font-bold">
                        دیاریکردنی هەموو
                    </button>
                    <button type="button" onclick="checkAllPermissions(false)" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-750 text-slate-400 hover:text-white border border-slate-700 transition font-bold">
                        سڕینەوەی هەموو
                    </button>
                </div>
            </div>

            <!-- Permission Groups Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @php
                    $groupLabels = [
                        'gyms' => ['title' => '🏛️ بەڕێوەبردنی هۆڵەکان', 'color' => 'teal'],
                        'members' => ['title' => '👥 ئەندامان و بەشداری', 'color' => 'indigo'],
                        'finance' => ['title' => '💰 دارایی، سندوق و قەرز', 'color' => 'emerald'],
                        'access' => ['title' => '🚪 دەرگای زیرەک و ئامادەبووان', 'color' => 'cyan'],
                        'shop' => ['title' => '🛍️ شۆپی تەواوکەر و POS', 'color' => 'amber'],
                        'reports' => ['title' => '📊 ڕاپۆرتەکان', 'color' => 'violet'],
                        'settings' => ['title' => '⚙️ ڕێکخستنەکان', 'color' => 'rose'],
                    ];
                @endphp

                @foreach($permissions as $groupName => $perms)
                    @php
                        $groupMeta = $groupLabels[$groupName] ?? ['title' => ucfirst($groupName), 'color' => 'slate'];
                    @endphp
                    <div class="bg-slate-850/80 border border-slate-800 rounded-2xl p-4 space-y-3 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-center border-b border-slate-800/80 pb-2 mb-3">
                                <span class="font-bold text-xs text-white">
                                    {{ $groupMeta['title'] }}
                                </span>
                                <span class="text-[10px] text-slate-500 font-mono">
                                    {{ $perms->count() }} مۆڵەت
                                </span>
                            </div>

                            <div class="space-y-2.5">
                                @foreach($perms as $p)
                                    @php
                                        $hasFromRole = in_array($p->id, $rolePermissionIds);
                                        $hasDirect = in_array($p->id, $directPermissionIds);
                                        $isChecked = $hasDirect || $hasFromRole;
                                    @endphp
                                    <label class="flex items-start gap-2.5 p-2 rounded-xl hover:bg-slate-800/70 cursor-pointer transition select-none group">
                                        <input type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $p->id }}" 
                                               data-permission-id="{{ $p->id }}"
                                               {{ $isChecked ? 'checked' : '' }}
                                               class="permission-checkbox mt-0.5 w-4 h-4 rounded bg-slate-800 border-slate-700 text-emerald-500 focus:ring-0">
                                        
                                        <div class="min-w-0 flex-1">
                                            <span class="text-xs font-bold text-slate-200 block group-hover:text-white transition">
                                                {{ $p->display_name_ku }}
                                            </span>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] font-mono text-slate-500 block truncate">
                                                    {{ $p->name }}
                                                </span>
                                                @if($hasFromRole)
                                                    <span class="text-[9px] px-1.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold" title="ئەم مۆڵەتە لە ڕۆڵە سەرەکییەکەیەوە بەخشراوە">
                                                        لە ڕۆڵەکەیەوە
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-800/50 flex justify-end">
                            <button type="button" onclick="toggleGroupCheckboxes(this, true)" class="text-[10px] text-slate-400 hover:text-emerald-400 font-bold transition ml-2">
                                دیاریکردن
                            </button>
                            <span class="text-slate-600 text-[10px]">•</span>
                            <button type="button" onclick="toggleGroupCheckboxes(this, false)" class="text-[10px] text-slate-400 hover:text-rose-400 font-bold transition mr-2">
                                هەڵوەشاندنەوە
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Submit & Actions Footer -->
        <div class="flex justify-end items-center gap-3 bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl">
            <a href="{{ route('users.index') }}" class="px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-400 bg-slate-800 hover:bg-slate-750 transition">
                پەشیمانبوونەوە
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-2xl text-xs font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-500 hover:to-teal-400 transition shadow-lg shadow-emerald-600/30 flex items-center gap-2">
                <span>💾 پاشەکەوتکردنی مۆڵەتەکان و زانیاری بەکارهێنەر</span>
            </button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    function checkAllPermissions(status) {
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            cb.checked = status;
        });
    }

    function toggleGroupCheckboxes(btn, status) {
        const groupContainer = btn.closest('.bg-slate-850\\/80');
        if (groupContainer) {
            groupContainer.querySelectorAll('.permission-checkbox').forEach(cb => {
                cb.checked = status;
            });
        }
    }

    function handleRoleChange() {
        const select = document.getElementById('role-select');
        const selectedOption = select.options[select.selectedIndex];
        const permsData = selectedOption.getAttribute('data-permissions');

        if (permsData) {
            try {
                const rolePermIds = JSON.parse(permsData);
                // Highlight checkboxes associated with this role
                document.querySelectorAll('.permission-checkbox').forEach(cb => {
                    const permId = parseInt(cb.getAttribute('data-permission-id'));
                    if (rolePermIds.includes(permId)) {
                        cb.checked = true;
                    }
                });
            } catch (e) {
                console.error(e);
            }
        }
    }
</script>
@endpush
