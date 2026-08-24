@extends('layouts.app')

@section('title', 'Inquiries')

@section('content')
@php
    $current = (int) ($pagination['current_page'] ?? 1);
    $totalPages = (int) ($pagination['total_pages'] ?? 1);
    $totalItems = (int) ($pagination['total_items'] ?? count($inquiries));
@endphp

<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Inquiries</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Cancellation requests and support tickets from ENOX AI.</p>
        </div>
    </div>

    <div class="section-card">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 items-end">
            <div>
                <label class="f-label">Status</label>
                <select name="status" class="f-input custom-select">
                    <option value="">All statuses</option>
                    @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="f-label">Category</label>
                <select name="category" class="f-input custom-select">
                    <option value="">All categories</option>
                    @foreach(['order_cancel_request', 'live_support_request', 'general_support', 'order_not_placed', 'delivery_delay'] as $cat)
                        <option value="{{ $cat }}" @selected(($filters['category'] ?? '') === $cat)>{{ \App\Support\ChatContent::inquiryCategoryLabel($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="xl:col-span-2">
                <label class="f-label">Search</label>
                <input class="f-input" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, email, order ID, subject">
            </div>
            <button class="btn-primary h-[38px]" type="submit">Filter</button>
        </form>
    </div>

    <div class="flex flex-row items-center justify-between py-1 px-0.5 min-h-[40px] mb-2">
        <p class="text-[12px] text-slate-400 dark:text-slate-500 m-0">
            @if($totalItems)
                Showing <span class="font-semibold text-slate-600 dark:text-slate-300">{{ count($inquiries) }}</span>
                of <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $totalItems }}</span> inquiries
            @else
                No inquiries found
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
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($inquiries as $item)
                    <tr>
                        <td class="font-mono text-[12px]">{{ $item['reference'] ?? '—' }}</td>
                        <td>
                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $item['name'] ?? 'Guest' }}</p>
                            <p class="text-[12px] text-slate-400">{{ $item['email'] ?? '—' }}</p>
                        </td>
                        <td>{{ \App\Support\ChatContent::inquiryCategoryLabel($item['category'] ?? null) }}</td>
                        <td class="max-w-[240px] truncate">{{ $item['subject'] ?: \Illuminate\Support\Str::limit($item['message'] ?? '', 60) }}</td>
                        <td>
                            <span class="badge badge-{{ \App\Support\ChatContent::inquiryStatusBadge($item['status'] ?? null) }}">
                                {{ \App\Support\ChatContent::inquiryStatusLabel($item['status'] ?? null) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap text-slate-500">{{ \App\Support\ChatContent::relativeTime($item['created_at'] ?? null) }}</td>
                        <td>
                            <a class="btn-secondary py-1.5 px-3 text-[12px]" href="{{ route('inquiries.show', $item['id']) }}">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-400 py-8">No inquiries yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
