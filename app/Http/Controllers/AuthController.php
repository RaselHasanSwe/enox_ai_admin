<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use App\Support\EnoxSession;
use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;

class AuthController extends Controller
{
    public function showLogin(EnoxApiClient $api)
    {
        if (EnoxSession::isAuthenticated()) {
            try {
                $api->me();
                return redirect()->route('dashboard');
            } catch (RequestException) {
                EnoxSession::clear();
            }
        }

        return view('auth.login');
    }

    public function login(Request $request, EnoxApiClient $api)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            $data = $api->login($credentials['email'], $credentials['password']);
        } catch (RequestException $e) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        session([
            'enox_access_token' => $data['access_token'],
            'enox_refresh_token' => $data['refresh_token'],
            'enox_user' => $data['user'],
        ]);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        EnoxSession::clear();

        return redirect()->route('login');
    }
}
