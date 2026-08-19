<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use App\Support\EnoxSession;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public const PERIODS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This week',
        'last_week' => 'Last week',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'custom' => 'Custom',
    ];

    public function index(Request $request, EnoxApiClient $api)
    {
        $period = $request->get('period', 'today');
        if (! array_key_exists($period, self::PERIODS)) {
            $period = 'today';
        }

        [$from, $to] = $this->periodRange($request, $period);
        $query = [
            'date_from' => $from->clone()->utc()->format('Y-m-d H:i:s'),
            'date_to' => $to->clone()->utc()->format('Y-m-d H:i:s'),
        ];

        try {
            $overview = $api->overview($query);
            $tools = $api->toolAnalytics($query);
            $csat = $api->csatAnalytics($query);
        } catch (RequestException $e) {
            EnoxSession::clear();

            return redirect()->route('login')->withErrors(['email' => 'Session expired. Please log in again.']);
        }

        return view('dashboard.index', [
            'overview' => $overview,
            'tools' => $tools['tools'] ?? [],
            'csat' => $csat,
            'user' => session('enox_user'),
            'period' => $period,
            'periodLabel' => $period === 'custom'
                ? $from->format('d M, H:i').' – '.$to->format('d M, H:i')
                : self::PERIODS[$period],
            'dateFrom' => $from,
            'dateTo' => $to,
            'periods' => self::PERIODS,
        ]);
    }

    protected function periodRange(Request $request, string $period): array
    {
        $tz = (string) config('enox.display_timezone', 'Europe/London');
        $now = Carbon::now($tz);

        if ($period === 'custom') {
            $from = $request->filled('date_from')
                ? Carbon::parse($request->input('date_from'), $tz)
                : $now->copy()->startOfDay();
            $to = $request->filled('date_to')
                ? Carbon::parse($request->input('date_to'), $tz)
                : $now->copy()->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            return [$from, $to];
        }

        return match ($period) {
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY),
                $now->copy(),
            ],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(Carbon::MONDAY),
                $now->copy()->subWeek()->endOfWeek(Carbon::SUNDAY),
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy(),
            ],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            default => [
                $now->copy()->startOfDay(),
                $now->copy(),
            ],
        };
    }
}
