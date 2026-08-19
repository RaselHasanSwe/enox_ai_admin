@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $queued = (int) ($overview['queued_conversations'] ?? 0);
    $today = (int) ($overview['conversations_today'] ?? 0);
    $containment = $overview['containment_rate_percent'] ?? 0;
    $csatAvg = $csat['average'] ?? $overview['csat_average'] ?? null;
    $csatCount = (int) ($overview['csat_count'] ?? 0);
    $firstReply = $overview['avg_agent_first_reply_sec'] ?? null;
    $aiMs = $overview['avg_ai_response_ms'] ?? null;
    $csatCounts = array_values($csat['distribution'] ?? []);
    $maxCsat = max($csatCounts ?: [0]) ?: 1;
    $topicMax = max(array_column($overview['top_topics'] ?? [], 'count') ?: [0]) ?: 1;
    $toolMax = max(array_column($tools, 'count') ?: [0]) ?: 1;

    $fmtReply = function ($sec) {
        if ($sec === null || $sec === '') return '—';
        $sec = (float) $sec;
        if ($sec < 60) return round($sec).'s';
        $m = intdiv((int) $sec, 60);
        $s = ((int) $sec) % 60;
        return $s ? $m.'m '.$s.'s' : $m.'m';
    };
    $fmtMs = function ($ms) {
        if ($ms === null || $ms === '') return '—';
        $ms = (int) $ms;
        return $ms >= 1000 ? number_format($ms / 1000, 1).'s' : $ms.'ms';
    };

    $charts = [
        'trend' => $overview['trend'] ?? [],
        'statusMix' => $overview['status_mix'] ?? [],
        'topics' => collect($overview['top_topics'] ?? [])->map(fn ($t) => [
            'label' => \Illuminate\Support\Str::headline($t['topic'] ?? ''),
            'count' => (int) ($t['count'] ?? 0),
        ])->all(),
        'tools' => collect($tools)->map(fn ($t) => [
            'label' => \Illuminate\Support\Str::headline(str_replace('_', ' ', $t['tool'] ?? '')),
            'count' => (int) ($t['count'] ?? 0),
        ])->all(),
        'timezone' => config('enox.display_timezone', 'Europe/London'),
    ];
@endphp

<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Dashboard</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Support operations: queue pressure, AI containment, and customer experience.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($queued > 0)
                <a href="{{ route('handoff.queue') }}" class="btn-primary">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    Open queue ({{ $queued }})
                </a>
            @endif
            <form method="POST" action="{{ route('presence') }}">
                @csrf
                <input type="hidden" name="is_online" value="1">
                <button class="btn-secondary" type="submit">Go online</button>
            </form>
        </div>
    </div>

    <form method="GET" class="dash-filters" id="dash-filter-form">
        <div class="dash-filter-pills">
            @foreach($periods as $key => $label)
                @if($key !== 'custom')
                    <a href="{{ route('dashboard', ['period' => $key]) }}" class="dash-filter {{ $period === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endif
            @endforeach
            <button type="button" class="dash-filter {{ $period === 'custom' ? 'is-active' : '' }}" id="dash-custom-toggle">Custom</button>
        </div>
        <div class="dash-filter-custom {{ $period === 'custom' ? '' : 'hidden' }}" id="dash-custom-fields">
            <input type="hidden" name="period" value="custom">
            <label class="f-label mb-0">From</label>
            <input class="f-input dash-filter-input" type="datetime-local" name="date_from" value="{{ $dateFrom->format('Y-m-d\TH:i') }}">
            <label class="f-label mb-0">To</label>
            <input class="f-input dash-filter-input" type="datetime-local" name="date_to" value="{{ $dateTo->format('Y-m-d\TH:i') }}">
            <button class="btn-primary h-[38px]" type="submit">Apply</button>
        </div>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-5">
        <a href="{{ route('handoff.queue') }}" class="kpi-card {{ $queued > 0 ? 'is-alert' : '' }} no-underline text-inherit">
            <p class="kpi-label">Waiting now</p>
            <p class="kpi-value">{{ $queued }}</p>
            <p class="kpi-meta">{{ $queued > 0 ? 'Customers in the live queue' : 'Queue is clear' }}</p>
        </a>
        <div class="kpi-card">
            <p class="kpi-label">Conversations</p>
            <p class="kpi-value">{{ $overview['conversations_in_period'] ?? $today }}</p>
            <p class="kpi-meta">{{ $periodLabel }}</p>
        </div>
        <div class="kpi-card">
            <p class="kpi-label">AI containment</p>
            <p class="kpi-value">{{ $containment }}%</p>
            <p class="kpi-meta">Chats closed without a human</p>
        </div>
        <div class="kpi-card">
            <p class="kpi-label">Customer rating</p>
            <p class="kpi-value">{{ $csatAvg !== null ? number_format((float) $csatAvg, 1) : '—' }}</p>
            <p class="kpi-meta">{{ $csatCount ? $csatCount.' ratings' : 'No ratings yet' }}</p>
        </div>
        <div class="kpi-card">
            <p class="kpi-label">Agent first reply</p>
            <p class="kpi-value">{{ $fmtReply($firstReply) }}</p>
            <p class="kpi-meta">Average after handoff</p>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="kpi-mini">
            <p class="kpi-label">Handoff rate</p>
            <p class="kpi-mini-value">{{ $overview['handoff_rate_percent'] ?? 0 }}%</p>
        </div>
        <div class="kpi-mini">
            <p class="kpi-label">AI response</p>
            <p class="kpi-mini-value">{{ $fmtMs($aiMs) }}</p>
        </div>
        <div class="kpi-mini">
            <p class="kpi-label">Online agents</p>
            <p class="kpi-mini-value">{{ $overview['online_agents'] ?? 0 }}</p>
        </div>
        <div class="kpi-mini">
            <p class="kpi-label">Repeat visitors</p>
            <p class="kpi-mini-value">{{ $overview['repeat_visitors'] ?? 0 }}</p>
        </div>
    </div>

    <script type="application/json" id="dashboard-charts">@json($charts)</script>

    <div class="dash-chart-grid mb-5">
        <div class="section-card mb-0 dash-chart-card">
            <div class="card-title">Conversation volume</div>
            <p class="kpi-meta -mt-3 mb-3">{{ $periodLabel }} · chats started vs human handoffs</p>
            <div class="dash-chart"><canvas id="chart-trend"></canvas></div>
        </div>
        <div class="section-card mb-0 dash-chart-card">
            <div class="card-title">Workload mix</div>
            <p class="kpi-meta -mt-3 mb-3">{{ $periodLabel }} · status of chats started in this period</p>
            <div class="dash-mix">
                <div class="dash-chart dash-chart--doughnut"><canvas id="chart-mix"></canvas></div>
                <ul class="dash-mix-legend" id="chart-mix-legend"></ul>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <div class="section-card mb-0">
            <div class="card-title">Why customers need a human</div>
            <div class="space-y-3">
                @forelse(($overview['top_topics'] ?? []) as $topic)
                    @php $pct = $topicMax ? round(((int) $topic['count'] / $topicMax) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-[12px] mb-1">
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::headline($topic['topic'] ?? '') }}</span>
                            <span class="text-slate-400">{{ $topic['count'] }}</span>
                        </div>
                        <div class="dash-bar"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @empty
                    <div class="empty-state py-8">No handoff topics yet.</div>
                @endforelse
            </div>
        </div>
        <div class="section-card mb-0">
            <div class="card-title">Tools the AI uses</div>
            <div class="space-y-3">
                @forelse($tools as $tool)
                    @php $pct = $toolMax ? round(((int) $tool['count'] / $toolMax) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-[12px] mb-1">
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $tool['tool'] ?? '')) }}</span>
                            <span class="text-slate-400">{{ $tool['count'] }}</span>
                        </div>
                        <div class="dash-bar"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @empty
                    <div class="empty-state py-8">No tool usage yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="section-card mb-0">
            <div class="card-title">Customer ratings</div>
            <div class="space-y-3">
                @forelse(($csat['distribution'] ?? []) as $rating => $count)
                    @php $pct = $maxCsat ? round(($count / $maxCsat) * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between text-[12px] mb-1">
                            <span class="font-medium text-slate-600 dark:text-slate-300">{{ $rating }} star</span>
                            <span class="text-slate-400">{{ $count }}</span>
                        </div>
                        <div class="dash-bar"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @empty
                    <div class="empty-state py-8">No ratings yet.</div>
                @endforelse
            </div>
        </div>
        <div class="section-card mb-0">
            <div class="card-title">Recent feedback</div>
            <div class="space-y-3">
                @forelse(array_slice($csat['recent'] ?? [], 0, 5) as $item)
                    <div class="rounded-xl border border-slate-100 dark:border-slate-700 p-3">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-[13px] font-semibold text-slate-800 dark:text-slate-100">{{ $item['name'] ?? 'Anonymous' }}</p>
                            <span class="badge badge-warning">{{ $item['rating'] }}/5</span>
                        </div>
                        <p class="text-[13px] text-slate-500 dark:text-slate-400">{{ $item['comment'] ?? 'No comment' }}</p>
                    </div>
                @empty
                    <div class="empty-state py-8">No feedback yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
