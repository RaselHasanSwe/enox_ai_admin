<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function businessHours(EnoxApiClient $api)
    {
        return view('settings.business-hours', [
            'hours' => $api->businessHours(),
            'user' => session('enox_user'),
        ]);
    }

    public function updateBusinessHours(Request $request, EnoxApiClient $api)
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'max:64'],
            'offline_message' => ['required', 'string', 'max:500'],
            'days' => ['required', 'array'],
        ]);

        $validated['enabled'] = $request->boolean('enabled');
        $api->updateBusinessHours($validated);

        return back()->with('status', 'Business hours updated.');
    }
}
