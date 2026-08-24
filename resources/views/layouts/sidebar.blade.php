@php
    $user = session('enox_user', []);
    $name = $user['name'] ?? 'Agent';

    if (! function_exists('sidebarLink')) {
        function sidebarLink(bool $active): string
        {
            return $active
                ? 'nav-link nav-active flex items-center gap-2.5 px-[18px] py-2 text-[13px] text-[#5DCAA5] bg-[rgba(93,202,165,0.10)]'
                : 'nav-link flex items-center gap-2.5 px-[18px] py-2 text-[13px] text-slate-300 hover:bg-white/[0.06] hover:text-white';
        }
    }

    if (! function_exists('sidebarIcon')) {
        function sidebarIcon(bool $active): string
        {
            return $active ? 'opacity-100' : 'opacity-75';
        }
    }
@endphp

<aside id="sidebar"
    class="w-[230px] min-w-[230px] bg-[#0c1521] flex flex-col overflow-y-auto z-50 lg:relative lg:translate-x-0 print:hidden transition-all duration-300"
    style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.1) transparent;">

    <div class="sidebar-header px-[18px] py-5 border-b border-white/[0.08] flex items-center justify-between flex-shrink-0">
        <div class="logo-text-group">
            <div class="text-white font-semibold tracking-[2.5px] text-[17px]">ENOX</div>
            <div class="text-[10px] tracking-[1.2px] text-slate-500 mt-0.5">AI Agent Admin</div>
        </div>
        <button id="sidebarToggle" type="button"
            class="hidden lg:inline-block text-slate-400 hover:text-white transition-colors duration-200"
            aria-label="Collapse sidebar">
            <svg id="menuIcon" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg id="closeIcon" class="w-6 h-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 py-2">
        <div class="pt-4 pb-1">
            <p class="menu-title text-[9px] tracking-[1.8px] uppercase text-slate-500 font-semibold px-[18px] pb-2">Overview</p>
            <a href="{{ route('dashboard') }}" class="{{ sidebarLink(request()->routeIs('dashboard') || request()->routeIs('analytics.*')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('dashboard') || request()->routeIs('analytics.*')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>
        </div>

        <div class="pt-4 pb-1">
            <p class="menu-title text-[9px] tracking-[1.8px] uppercase text-slate-500 font-semibold px-[18px] pb-2">Support</p>
            <a href="{{ route('handoff.queue') }}" class="{{ sidebarLink(request()->routeIs('handoff.queue') || request()->routeIs('handoff.chat')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('handoff.queue') || request()->routeIs('handoff.chat')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M12 22a10 10 0 100-20 10 10 0 000 20z"/>
                </svg>
                Live Queue
            </a>
            <a href="{{ route('handoff.active') }}" class="{{ sidebarLink(request()->routeIs('handoff.active')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('handoff.active')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.947L3 21l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                Active Chats
            </a>
            <a href="{{ route('conversations.index') }}" class="{{ sidebarLink(request()->routeIs('conversations.*')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('conversations.*')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                </svg>
                Conversations
            </a>
            <a href="{{ route('inquiries.index') }}" class="{{ sidebarLink(request()->routeIs('inquiries.*')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('inquiries.*')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Inquiries
            </a>
        </div>

        <div class="pt-4 pb-1">
            <p class="menu-title text-[9px] tracking-[1.8px] uppercase text-slate-500 font-semibold px-[18px] pb-2">People</p>
            <a href="{{ route('users.index') }}" class="{{ sidebarLink(request()->routeIs('users.*')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('users.*')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"/>
                    <path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
                Chat Users
            </a>
        </div>

        <div class="pt-4 pb-1">
            <p class="menu-title text-[9px] tracking-[1.8px] uppercase text-slate-500 font-semibold px-[18px] pb-2">Settings</p>
            <a href="{{ route('settings.business-hours') }}" class="{{ sidebarLink(request()->routeIs('settings.*')) }}">
                <svg class="w-4 h-4 flex-shrink-0 {{ sidebarIcon(request()->routeIs('settings.*')) }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3"/>
                    <path stroke-linecap="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                </svg>
                Business Hours
            </a>
        </div>
    </nav>
</aside>
