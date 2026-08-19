@extends('layouts.app')

@section('title', 'Business Hours')

@section('content')
@php
    $days = $hours['days'] ?? [];
    $dayLabels = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
    $enabled = (bool) ($hours['enabled'] ?? false);
@endphp

<div class="page-content">
    @if(session('status'))
        <div class="flash-banner">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-[17px] font-semibold text-slate-800 dark:text-slate-100 leading-tight">Business Hours</h1>
            <p class="text-[12px] text-slate-400 dark:text-slate-500 mt-0.5">Control when customers can request a live human handoff.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.business-hours.update') }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-[320px_1fr] gap-5">
            <div class="space-y-4">
                <div class="info-card">
                    <div class="card-title">Availability</div>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" class="sr-only" @checked($enabled) id="hours-enabled">
                        <span class="toggle-track {{ $enabled ? 'on' : '' }}" id="hours-toggle">
                            <span class="toggle-thumb"></span>
                        </span>
                        <span>
                            <span class="block text-[13px] font-medium text-slate-700 dark:text-slate-200">Enforce business hours</span>
                            <span class="block text-[12px] text-slate-400 mt-0.5">When on, live handoff is only offered during the schedule below.</span>
                        </span>
                    </label>
                </div>

                <div class="info-card">
                    <label class="f-label" for="timezone">Timezone</label>
                    <input class="f-input" id="timezone" name="timezone" value="{{ $hours['timezone'] ?? 'Europe/London' }}">
                    <p class="text-[11px] text-slate-400 mt-1">Used for open/close times on the weekly schedule.</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="section-card mb-0">
                    <div class="card-title">Offline message</div>
                    <textarea class="f-input" name="offline_message" rows="3">{{ $hours['offline_message'] ?? 'Our agents are currently offline. Please try again during business hours.' }}</textarea>
                </div>

                <div class="section-card mb-0 overflow-hidden p-0">
                    <div class="px-5 pt-5">
                        <div class="card-title">Weekly schedule</div>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr><th>Day</th><th>Open</th><th>Close</th></tr>
                        </thead>
                        <tbody>
                        @foreach($dayLabels as $key => $label)
                            <tr>
                                <td class="font-medium">{{ $label }}</td>
                                <td><input class="f-input mb-0 max-w-[140px]" type="time" name="days[{{ $key }}][open]" value="{{ $days[$key]['open'] ?? '09:00' }}"></td>
                                <td><input class="f-input mb-0 max-w-[140px]" type="time" name="days[{{ $key }}][close]" value="{{ $days[$key]['close'] ?? '17:00' }}"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="text-[12px] text-slate-400">
                    Also set <code class="text-accent-600 dark:text-accent-200">HANDOFF_BUSINESS_HOURS_ENFORCED=true</code> in the FastAPI <code>.env</code> to activate enforcement.
                </p>

                <button class="btn-primary" type="submit">Save settings</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
document.getElementById('hours-enabled')?.addEventListener('change', function () {
    document.getElementById('hours-toggle')?.classList.toggle('on', this.checked);
});
</script>
@endpush
