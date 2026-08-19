@extends('layouts.app')

@section('title', 'My Active Chats')

@section('content')
<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">My Active Chats</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Conversations currently assigned to you.</p>
        </div>
        <a href="{{ route('handoff.queue') }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
            Back to queue
        </a>
    </div>

    <div class="section-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Reason</th>
                        <th>Last activity</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($active as $item)
                    <tr>
                        <td>
                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $item['user_name'] ?: 'Guest' }}</p>
                            <p class="text-[12px] text-slate-400">{{ $item['user_email'] ?: '—' }}</p>
                        </td>
                        <td>{{ $item['handoff_reason'] ?? '—' }}</td>
                        <td class="whitespace-nowrap text-slate-500">
                            <span data-wait-since="{{ $item['last_message_at'] ?? '' }}">{{ \App\Support\ChatContent::relativeTime($item['last_message_at'] ?? null) }}</span>
                        </td>
                        <td>
                            <a class="btn-primary py-1.5 px-3 text-[12px]" href="{{ route('handoff.chat', $item['id']) }}">Open chat</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">No active chats assigned to you.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
