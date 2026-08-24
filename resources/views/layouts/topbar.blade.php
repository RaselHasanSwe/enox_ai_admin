@php
    $user = session('enox_user', []);
    $name = $user['name'] ?? 'Agent';
    $email = $user['email'] ?? '';
    $initials = collect(explode(' ', $name))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="h-14 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between px-5 flex-shrink-0 gap-3">
    <div class="flex items-center gap-3 min-w-0">
        <button type="button" onclick="toggleSidebar()" class="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" aria-label="Toggle sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="lg:hidden text-sm font-semibold tracking-[2px] text-slate-800 dark:text-slate-100">ENOX</div>

        <h4 class="hidden lg:block text-sm font-semibold text-slate-700 dark:text-slate-200 uppercase truncate">Hi, {{ $name }}</h4>
    </div>

    <div class="flex items-center gap-2 flex-shrink-0">
        <div class="relative">
            <button type="button" id="notify-bell" onclick="toggleNotifyDropdown(event)"
                    class="relative p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    title="Notifications"
                    aria-label="Notifications">
                <svg class="w-4.5 h-4.5 w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/>
                </svg>
                <span id="notify-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-bold flex items-center justify-center">0</span>
            </button>
            <div id="notify-menu"
                 class="absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg shadow-slate-200/50 dark:shadow-slate-900/50 z-50 overflow-hidden"
                 style="display: none;">
                <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100 dark:border-slate-700">
                    <p class="text-[12px] font-semibold text-slate-700 dark:text-slate-200">Notifications</p>
                    <button type="button" id="notify-mark-read" class="text-[11px] text-accent-400 hover:text-accent-600">Mark all read</button>
                </div>
                <div id="notify-list" class="max-h-80 overflow-y-auto"></div>
            </div>
        </div>

        <button type="button" onclick="toggleDark()"
                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                title="Toggle dark mode"
                aria-label="Toggle dark mode">
            <svg id="iconSun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="5"/>
                <path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            <svg id="iconMoon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
            </svg>
        </button>

        <div class="relative">
            <button type="button" onclick="toggleUserDropdown(event)"
                    class="flex items-center gap-2 p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors focus:outline-none"
                    id="user-dropdown-btn">
                <span class="avatar-initials w-8 h-8 text-[11px]">{{ $initials ?: 'A' }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" id="user-dropdown-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div id="user-dropdown-menu"
                 class="absolute right-0 mt-2 w-52 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl shadow-lg shadow-slate-200/50 dark:shadow-slate-900/50 py-1.5 z-50 transition-all duration-150 origin-top-right"
                 style="display: none;">
                <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700">
                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Welcome {{ \Illuminate\Support\Str::limit($name, 14) }}!</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate">{{ $email }}</p>
                </div>

                <a href="{{ route('settings.business-hours') }}"
                   class="flex items-center gap-2.5 px-4 py-2 text-[13px] text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3"/>
                        <path stroke-linecap="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                    </svg>
                    Settings
                </a>

                <div class="my-1.5 border-t border-slate-100 dark:border-slate-700"></div>

                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="flex items-center gap-2.5 px-4 py-2 text-[13px] text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    Logout
                </a>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</header>
