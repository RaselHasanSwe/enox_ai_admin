<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, EnoxApiClient $api)
    {
        $filters = $request->only(['search']);
        $data = $api->users(array_filter([
            'page' => $request->get('page', 1),
            'search' => $filters['search'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''));

        return view('users.index', [
            'users' => $data['data'] ?? [],
            'pagination' => $data['pagination'] ?? [],
            'filters' => $filters,
            'user' => session('enox_user'),
        ]);
    }

    public function show(int $id, Request $request, EnoxApiClient $api)
    {
        $query = [];
        if ($request->filled('conversation')) {
            $query['conversation_id'] = $request->integer('conversation');
        }

        try {
            $data = $api->user($id, $query);
        } catch (RequestException $e) {
            abort($e->response?->status() === 404 ? 404 : 502);
        }

        return view('users.show', [
            'chatUser' => $data['user'] ?? [],
            'conversations' => $data['conversations'] ?? [],
            'conversation' => $data['conversation'] ?? [],
            'messages' => $data['messages'] ?? [],
            'user' => session('enox_user'),
        ]);
    }
}
