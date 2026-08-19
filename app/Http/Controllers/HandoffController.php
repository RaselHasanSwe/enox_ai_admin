<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use Illuminate\Http\Request;

class HandoffController extends Controller
{
    public function queue(Request $request, EnoxApiClient $api)
    {
        $data = $api->handoffQueue();
        $me = $api->me();
        $queue = $data['queue'] ?? [];
        $focusId = (int) $request->query('focus', 0);

        if ($focusId) {
            usort($queue, function ($a, $b) use ($focusId) {
                $aHit = (int) ($a['id'] ?? 0) === $focusId ? 0 : 1;
                $bHit = (int) ($b['id'] ?? 0) === $focusId ? 0 : 1;
                if ($aHit !== $bHit) {
                    return $aHit <=> $bHit;
                }

                return strcmp((string) ($a['queued_at'] ?? ''), (string) ($b['queued_at'] ?? ''));
            });
        }

        return view('handoff.queue', [
            'queue' => $queue,
            'focusId' => $focusId,
            'user' => session('enox_user'),
            'isOnline' => (bool) ($me['is_online'] ?? false),
            'apiUrl' => config('enox.api_url'),
            'wsUrl' => config('enox.ws_url'),
            'token' => session('enox_access_token'),
        ]);
    }

    public function active(EnoxApiClient $api)
    {
        $data = $api->activeHandoffs(mine: true);

        return view('handoff.active', [
            'active' => $data['active'] ?? [],
            'user' => session('enox_user'),
        ]);
    }

    public function claim(int $id, EnoxApiClient $api)
    {
        $api->claimHandoff($id);

        return redirect()->route('handoff.chat', $id);
    }

    public function chat(int $id, EnoxApiClient $api)
    {
        $data = $api->conversation($id);

        return view('handoff.chat', [
            'conversation' => $data['conversation'] ?? [],
            'messages' => $data['messages'] ?? [],
            'user' => session('enox_user'),
            'apiUrl' => config('enox.api_url'),
            'wsUrl' => config('enox.ws_url'),
            'token' => session('enox_access_token'),
        ]);
    }

    public function sendMessage(int $id, Request $request, EnoxApiClient $api)
    {
        $request->validate(['message' => ['required', 'string', 'max:2000']]);

        if ($request->expectsJson()) {
            $api->sendHandoffMessage($id, $request->message);

            return response()->json(['status' => true]);
        }

        $api->sendHandoffMessage($id, $request->message);

        return back();
    }

    public function release(int $id, EnoxApiClient $api)
    {
        $api->releaseHandoff($id);

        return redirect()->route('handoff.queue')->with('status', 'Conversation returned to queue.');
    }

    public function resolve(int $id, EnoxApiClient $api)
    {
        $api->resolveHandoff($id);

        return redirect()->route('handoff.queue')->with('status', 'Conversation resolved.');
    }

    public function presence(Request $request, EnoxApiClient $api)
    {
        $request->validate(['is_online' => ['required', 'boolean']]);
        $api->setPresence($request->boolean('is_online'));

        return back()->with('status', 'Presence updated.');
    }
}
