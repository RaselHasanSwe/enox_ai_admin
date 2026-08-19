@extends('layouts.app')

@section('title', 'Chat Users')

@section('content')
@php
    $current = (int) ($pagination['current_page'] ?? 1);
    $totalPages = (int) ($pagination['total_pages'] ?? 1);
    $totalItems = (int) ($pagination['total_items'] ?? count($users));
@endphp

<div class="page-content">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Chat Users</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Customers who have talked to the EnoX assistant.</p>
        </div>
    </div>

    <div class="section-card">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-end">
            <div>
                <label class="f-label" for="user-search">Search</label>
                <input id="user-search" class="f-input" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or email">
            </div>
            <div class="flex items-center gap-2">
                <button class="btn-primary h-[38px]" type="submit">Filter</button>
                @if(!empty($filters['search']))
                    <a class="btn-secondary h-[38px]" href="{{ route('users.index') }}">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="flex flex-row items-center justify-between py-1 px-0.5 min-h-[40px] mb-2">
        <p class="text-[12px] text-slate-400 dark:text-slate-500 m-0">
            @if($totalItems)
                Showing <span class="font-semibold text-slate-600 dark:text-slate-300">{{ count($users) }}</span>
                of <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $totalItems }}</span> users
            @else
                No users found
            @endif
        </p>
        @if($totalPages > 1)
            <div class="flex items-center gap-1 text-[12px]">
                @if($current > 1)
                    <a class="px-2 py-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" href="{{ request()->fullUrlWithQuery(['page' => $current - 1]) }}">Prev</a>
                @endif
                <span class="px-2 py-1 rounded-lg bg-accent-400 text-white font-semibold">{{ $current }}</span>
                <span class="text-slate-400">/ {{ $totalPages }}</span>
                @if($current < $totalPages)
                    <a class="px-2 py-1 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700" href="{{ request()->fullUrlWithQuery(['page' => $current + 1]) }}">Next</a>
                @endif
            </div>
        @endif
    </div>

    @if(count($users))
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
            @foreach($users as $u)
                @php
                    $displayName = $u['name'] ?: 'Guest';
                    $initials = collect(explode(' ', $displayName))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                    $lastActive = \App\Support\ChatContent::formatTimestamp($u['last_active'] ?? null) ?: '—';
                @endphp
                <a href="{{ route('users.show', $u['id']) }}" class="user-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden flex flex-col no-underline text-inherit">
                    <div class="h-1.5 bg-linear-to-r from-accent-200 via-accent-400 to-accent-600 opacity-80"></div>
                    <div class="p-4 flex-1">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="avatar-initials w-11 h-11 text-sm flex-shrink-0">{{ $initials ?: 'G' }}</span>
                            <div class="min-w-0">
                                <p class="text-[13.5px] font-semibold text-slate-800 dark:text-slate-100 leading-snug truncate">{{ $displayName }}</p>
                                <p class="text-[12px] text-slate-400 truncate">{{ $u['email'] ?: 'No email' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="row-label">Messages</p>
                                <p class="row-value font-semibold">{{ $u['message_count'] ?? 0 }}</p>
                            </div>
                            <div>
                                <p class="row-label">Last active</p>
                                <p class="row-value" title="{{ $u['last_active'] ?? '' }}">{{ $lastActive }}</p>
                            </div>
                        </div>
                        <p class="text-[12px] text-accent-600 dark:text-accent-300 mt-4 font-medium">View messages</p>
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="section-card">
            <div class="empty-state">No chat users yet.</div>
        </div>
    @endif
</div>
@endsection
