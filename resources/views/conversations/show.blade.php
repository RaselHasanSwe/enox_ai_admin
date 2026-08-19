@extends('layouts.app')

@section('title', 'Conversation #'.($conversation['id'] ?? ''))

@section('content')
@php
    $status = $conversation['status'] ?? 'unknown';
@endphp

<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">{{ $conversation['user_name'] ?? 'Conversation' }}</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">{{ $conversation['user_email'] ?? 'No email' }} · Conversation #{{ $conversation['id'] ?? '' }}</p>
        </div>
        <a href="{{ route('conversations.index') }}" class="btn-secondary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-5">
        <div class="space-y-4">
            <div class="info-card">
                <div class="card-title">
                    <svg class="w-4 h-4 text-accent-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    Details
                </div>
                <div class="kv mb-3">
                    <p class="row-label">Status</p>
                    <span class="badge badge-{{ $status }}">{{ str_replace('_', ' ', $status) }}</span>
                </div>
                @if(!empty($conversation['agent_name']))
                <div class="kv mb-3">
                    <p class="row-label">Agent</p>
                    <p class="row-value">{{ $conversation['agent_name'] }}</p>
                </div>
                @endif
                @if(!empty($conversation['handoff_summary']))
                <div class="kv mb-0">
                    <x-handoff.summary
                        :summary="$conversation['handoff_summary']"
                        :reason="$conversation['handoff_reason'] ?? null"
                        :messages="$messages"
                        :agent-name="$conversation['agent_name'] ?? ($user['name'] ?? null)"
                    />
                </div>
                @endif
            </div>

            <div class="info-card">
                <form method="POST" action="{{ route('conversations.tags', $conversation['id']) }}">
                    @csrf
                    <label class="f-label">Tags</label>
                    <input class="f-input" name="tags" value="{{ implode(', ', $conversation['tags'] ?? []) }}" placeholder="billing, vip, complaint">
                    <p class="text-[11px] text-slate-400 mt-1 mb-3">Comma-separated labels for this conversation.</p>
                    <button class="btn-primary w-full" type="submit">Save tags</button>
                </form>
            </div>
        </div>

        <div class="section-card mb-0">
            <div class="card-title">Transcript</div>
            <div class="chat-thread chat-thread--transcript">
                @forelse($messages as $msg)
                    <x-chat.message :message="$msg" :show-meta="true" :agent-name="$conversation['agent_name'] ?? ($user['name'] ?? null)" />
                @empty
                    <div class="empty-state">No messages in this conversation.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
