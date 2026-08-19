@extends('layouts.app')

@section('title', ($chatUser['name'] ?? 'User').' messages')

@section('content')
@php
    $status = $conversation['status'] ?? null;
    $selectedId = (int) ($conversation['id'] ?? 0);
@endphp

<div class="page-content">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">{{ $chatUser['name'] ?: 'Guest' }}</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $chatUser['email'] ?: 'No email' }}</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
            Back to users
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-5">
        <div class="space-y-4">
            <div class="info-card">
                <div class="card-title">Conversations</div>
                @forelse($conversations as $conv)
                    <a href="{{ route('users.show', ['id' => $chatUser['id'], 'conversation' => $conv['id']]) }}"
                       class="block rounded-xl px-3 py-2.5 mb-1 no-underline {{ $selectedId === (int) $conv['id'] ? 'bg-accent-50 dark:bg-slate-700' : 'hover:bg-slate-50 dark:hover:bg-slate-700/60' }}">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[13px] font-semibold text-slate-800 dark:text-slate-100">#{{ $conv['id'] }}</span>
                            <span class="badge badge-{{ $conv['status'] ?? 'inactive' }}">{{ str_replace('_', ' ', $conv['status'] ?? 'unknown') }}</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">{{ $conv['message_count'] ?? 0 }} messages · {{ \App\Support\ChatContent::formatTimestamp($conv['last_message_at'] ?? null) ?: '—' }}</p>
                    </a>
                @empty
                    <p class="text-[13px] text-slate-400">No conversations yet.</p>
                @endforelse
            </div>
        </div>

        <div class="section-card mb-0">
            <div class="card-title">
                Messages
                @if($status)
                    <span class="badge badge-{{ $status }} ml-2">{{ str_replace('_', ' ', $status) }}</span>
                @endif
            </div>
            <div class="chat-thread chat-thread--transcript">
                @forelse($messages as $msg)
                    <x-chat.message :message="$msg" :show-meta="true" :agent-name="$conversation['agent_name'] ?? null" />
                @empty
                    <div class="empty-state">No messages for this user yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
