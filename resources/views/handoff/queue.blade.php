@extends('layouts.app')

@section('title', 'Live Handoff Queue')

@section('content')
<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    @if($focusId && !collect($queue)->contains(fn ($item) => (int) ($item['id'] ?? 0) === (int) $focusId))
        <div class="flash-banner flash-banner--error">That request is no longer in the queue — it may already have been claimed.</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Live Handoff Queue</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Customers waiting for a human agent. Claim a chat to start helping.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('handoff.active') }}" class="btn-secondary">My active chats</a>
            <form method="POST" action="{{ route('presence') }}">
                @csrf
                <input type="hidden" name="is_online" value="{{ $isOnline ? '0' : '1' }}">
                <button class="{{ $isOnline ? 'btn-secondary' : 'btn-primary' }}" type="submit">
                    <span class="status-dot {{ $isOnline ? 'online' : '' }}"></span>
                    {{ $isOnline ? 'Go offline' : "I'm online" }}
                </button>
            </form>
        </div>
    </div>

    <div class="section-card overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Reason</th>
                        <th>Summary</th>
                        <th>Waiting since</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="queue-body">
                @forelse($queue as $item)
                    <tr data-id="{{ $item['id'] }}" class="{{ (int) $focusId === (int) $item['id'] ? 'queue-focus' : '' }}">
                        <td>
                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $item['user_name'] ?: 'Guest' }}</p>
                            <p class="text-[12px] text-slate-400">{{ $item['user_email'] ?: '—' }}</p>
                        </td>
                        <td>{{ $item['handoff_reason'] ?? '—' }}</td>
                        <td class="max-w-[320px] text-slate-500 dark:text-slate-400">{{ \App\Support\ChatContent::summaryText($item['handoff_summary'] ?? '') }}</td>
                        <td class="whitespace-nowrap text-slate-500">
                            <span data-wait-since="{{ $item['queued_at'] ?? '' }}">{{ \App\Support\ChatContent::relativeTime($item['queued_at'] ?? null) }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('handoff.claim', $item['id']) }}">
                                @csrf
                                <button class="btn-primary py-1.5 px-3 text-[12px]" type="submit">Claim</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr id="queue-empty">
                        <td colspan="5">
                            <div class="empty-state">No customers waiting.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const apiUrl = @json(rtrim($apiUrl, '/'));
    const token = @json($token);
    const csrf = @json(csrf_token());
    const focusId = @json((int) $focusId);
    const queueBody = document.getElementById('queue-body');

    function rowHtml(item) {
        const focused = Number(item.id) === Number(focusId) ? 'queue-focus' : '';
        return `
            <td>
                <p class="font-semibold text-slate-800 dark:text-slate-100">${EnoxChat.escapeHtml(item.user_name || 'Guest')}</p>
                <p class="text-[12px] text-slate-400">${EnoxChat.escapeHtml(item.user_email || '—')}</p>
            </td>
            <td>${EnoxChat.escapeHtml(item.handoff_reason || '—')}</td>
            <td class="max-w-[320px] text-slate-500 dark:text-slate-400">${EnoxChat.escapeHtml(EnoxChat.summaryText(item.handoff_summary || ''))}</td>
            <td class="whitespace-nowrap text-slate-500"><span data-wait-since="${EnoxChat.escapeHtml(item.queued_at || '')}">${EnoxChat.formatWait(item.queued_at)}</span></td>
            <td>
                <form method="POST" action="/handoff/${item.id}/claim">
                    <input type="hidden" name="_token" value="${csrf}">
                    <button class="btn-primary py-1.5 px-3 text-[12px]" type="submit">Claim</button>
                </form>
            </td>`;
    }

    function paint(queue) {
        let rows = queue || [];
        if (focusId) {
            rows = [...rows].sort((a, b) => (Number(a.id) === focusId ? 0 : 1) - (Number(b.id) === focusId ? 0 : 1));
        }
        queueBody.innerHTML = '';
        if (!rows.length) {
            queueBody.innerHTML = '<tr id="queue-empty"><td colspan="5"><div class="empty-state">No customers waiting.</div></td></tr>';
            return;
        }
        rows.forEach((item) => {
            const tr = document.createElement('tr');
            tr.dataset.id = item.id;
            if (Number(item.id) === Number(focusId)) tr.className = 'queue-focus';
            tr.innerHTML = rowHtml(item);
            queueBody.appendChild(tr);
        });
        const focused = queueBody.querySelector('.queue-focus');
        if (focused) focused.scrollIntoView({ block: 'center', behavior: 'smooth' });
        EnoxChat.tickWaitTimes();
    }

    async function refreshQueue() {
        try {
            const res = await fetch(apiUrl + '/admin/handoff/queue', {
                headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            paint(data.queue || []);
        } catch (e) {}
    }

    window.addEventListener('enox:queue-refresh', refreshQueue);
    setInterval(refreshQueue, 15000);

    const focused = document.querySelector('.queue-focus');
    if (focused) focused.scrollIntoView({ block: 'center' });
});
</script>
@endpush
