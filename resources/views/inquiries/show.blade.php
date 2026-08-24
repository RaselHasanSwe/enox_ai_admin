@extends('layouts.app')

@section('title', 'Inquiry ' . ($inquiry['reference'] ?? ''))

@section('content')
<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('inquiries.index') }}" class="text-[12px] text-accent-400 hover:text-accent-600">← Back to inquiries</a>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight mt-1">
                {{ $inquiry['reference'] ?? 'Inquiry' }}
            </h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">
                {{ \App\Support\ChatContent::inquiryCategoryLabel($inquiry['category'] ?? null) }}
                · {{ \App\Support\ChatContent::inquiryStatusLabel($inquiry['status'] ?? null) }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-4">
            <div class="section-card">
                <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-3">Message</h2>
                @if(!empty($inquiry['subject']))
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-200 mb-2">{{ $inquiry['subject'] }}</p>
                @endif
                <p class="text-sm text-slate-600 dark:text-slate-300 whitespace-pre-wrap">{{ $inquiry['message'] ?? '—' }}</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="section-card">
                <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-3">Customer</h2>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-slate-400 text-[11px] uppercase tracking-wide">Name</dt><dd>{{ $inquiry['name'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-[11px] uppercase tracking-wide">Email</dt><dd>{{ $inquiry['email'] ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400 text-[11px] uppercase tracking-wide">Phone</dt><dd>{{ $inquiry['phone'] ?: '—' }}</dd></div>
                    @if(!empty($inquiry['order_id']))
                        <div><dt class="text-slate-400 text-[11px] uppercase tracking-wide">Order ID</dt><dd class="font-mono">{{ $inquiry['order_id'] }}</dd></div>
                    @endif
                    <div><dt class="text-slate-400 text-[11px] uppercase tracking-wide">Source</dt><dd>{{ strtoupper($inquiry['source'] ?? 'enox_ai') }}</dd></div>
                    <div><dt class="text-slate-400 text-[11px] uppercase tracking-wide">Created</dt><dd>{{ \App\Support\ChatContent::relativeTime($inquiry['created_at'] ?? null) }}</dd></div>
                </dl>
            </div>

            <div class="section-card">
                <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100 mb-3">Update status</h2>
                <form method="POST" action="{{ route('inquiries.status', $inquiry['id']) }}" class="space-y-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="f-label">Status</label>
                        <select name="status" class="f-input custom-select">
                            @foreach(['pending', 'in_progress', 'resolved', 'rejected'] as $status)
                                <option value="{{ $status }}" @selected(($inquiry['status'] ?? '') === $status)>
                                    {{ \App\Support\ChatContent::inquiryStatusLabel($status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="f-label">Internal note (optional)</label>
                        <textarea name="note" class="f-input min-h-[80px]" placeholder="Resolution notes for the team">{{ $inquiry['metadata']['resolution_note'] ?? '' }}</textarea>
                    </div>
                    <button class="btn-primary w-full" type="submit">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
