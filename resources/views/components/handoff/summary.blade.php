@props(['summary' => '', 'reason' => null, 'messages' => [], 'agentName' => null])

@php
    $parsed = \App\Support\ChatContent::parseSummary($summary);
    $recent = collect($messages)->slice(-10)->values();
    $hasPanel = $parsed['intent'] || $reason || $parsed['tools'] || $recent->isNotEmpty();
@endphp

@if($hasPanel)
<aside class="handoff-panel">
    <div class="handoff-panel-head">
        <span class="handoff-summary-title">Handoff</span>
        <span class="badge badge-info">{{ $parsed['intent_label'] }}</span>
    </div>

    @if($reason)
        <p class="handoff-brief-reason">{{ $reason }}</p>
    @endif

    @if($parsed['tools'])
        <p class="handoff-summary-tools">Tools: {{ implode(', ', $parsed['tools']) }}</p>
    @endif

    @if($recent->isNotEmpty())
        <p class="handoff-brief-label">Last {{ $recent->count() }} messages</p>
        <div class="handoff-panel-thread">
            @foreach($recent as $msg)
                <x-chat.message :message="$msg" :show-meta="true" :agent-name="$agentName" />
            @endforeach
        </div>
    @endif
</aside>
@endif
