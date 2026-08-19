@extends('layouts.app')

@section('title', 'Conversations')

@section('content')
@php
    $current = (int) ($pagination['current_page'] ?? 1);
    $totalPages = (int) ($pagination['total_pages'] ?? 1);
    $totalItems = (int) ($pagination['total_items'] ?? count($conversations));
@endphp

<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Conversations</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Search, filter, and review customer chat history.</p>
        </div>
        <a class="btn-secondary" href="{{ route('conversations.export') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            Export CSV
        </a>
    </div>

    <div class="section-card">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 items-end">
            <div>
                <label class="f-label">Status</label>
                <select name="status" class="f-input custom-select">
                    <option value="">All statuses</option>
                    @foreach(['bot' => 'Bot', 'queued' => 'Queued', 'with_agent' => 'With agent', 'resolved' => 'Resolved'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="f-label">Email</label>
                <input class="f-input" name="email" value="{{ $filters['email'] ?? '' }}" placeholder="customer@email.com">
            </div>
            <div class="xl:col-span-2">
                <label class="f-label">Search</label>
                <input class="f-input" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, or message">
            </div>
            <button class="btn-primary h-[38px]" type="submit">Filter</button>
        </form>
    </div>

    <div class="flex flex-row items-center justify-between py-1 px-0.5 min-h-[40px] mb-2">
        <p class="text-[12px] text-slate-400 dark:text-slate-500 m-0">
            @if($totalItems)
                Showing <span class="font-semibold text-slate-600 dark:text-slate-300">{{ count($conversations) }}</span>
                of <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $totalItems }}</span> conversations
            @else
                No conversations found
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

    <div class="section-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Tags</th>
                        <th>Messages</th>
                        <th>Last activity</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($conversations as $conv)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $conv['user_name'] ?: 'Guest' }}</p>
                            <p class="text-[12px] text-slate-400">{{ $conv['user_email'] ?: '—' }}</p>
                        </td>
                        <td><span class="badge badge-{{ $conv['status'] ?? 'inactive' }}">{{ str_replace('_', ' ', $conv['status'] ?? 'unknown') }}</span></td>
                        <td>
                            @forelse(($conv['tags'] ?? []) as $tag)
                                <span class="badge badge-role">{{ $tag }}</span>
                            @empty
                                <span class="text-slate-400">—</span>
                            @endforelse
                        </td>
                        <td>{{ $conv['message_count'] ?? 0 }}</td>
                        <td class="text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ $conv['last_message_at'] ?? '' }}</td>
                        <td>
                            <a href="{{ route('conversations.show', $conv['id']) }}" class="btn-secondary py-1.5 px-3 text-[12px]">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 10h8M8 14h5M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.255-.947L3 21l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                No conversations yet.
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
