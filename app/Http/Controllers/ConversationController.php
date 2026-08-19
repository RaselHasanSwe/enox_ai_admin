<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request, EnoxApiClient $api)
    {
        $data = $api->conversations($request->only(['page', 'status', 'email', 'search']));

        return view('conversations.index', [
            'conversations' => $data['data'] ?? [],
            'pagination' => $data['pagination'] ?? [],
            'filters' => $request->only(['status', 'email', 'search']),
            'user' => session('enox_user'),
        ]);
    }

    public function show(int $id, EnoxApiClient $api)
    {
        $data = $api->conversation($id);

        return view('conversations.show', [
            'conversation' => $data['conversation'] ?? [],
            'messages' => $data['messages'] ?? [],
            'user' => session('enox_user'),
        ]);
    }

    public function updateTags(int $id, Request $request, EnoxApiClient $api)
    {
        $request->validate([
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $tags = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $request->input('tags', ''))
        )));

        $api->setTags($id, $tags);

        return back()->with('status', 'Tags updated.');
    }

    public function export(EnoxApiClient $api, Request $request)
    {
        $csv = $api->exportConversations();

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="conversations.csv"',
        ]);
    }
}
