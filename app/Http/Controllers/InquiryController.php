<?php

namespace App\Http\Controllers;

use App\Services\EnoxApiClient;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index(Request $request, EnoxApiClient $api)
    {
        $data = $api->inquiries($request->only(['page', 'status', 'category', 'search']));

        return view('inquiries.index', [
            'inquiries' => $data['data'] ?? [],
            'pagination' => $data['pagination'] ?? [],
            'filters' => $request->only(['status', 'category', 'search']),
            'user' => session('enox_user'),
        ]);
    }

    public function show(int $id, EnoxApiClient $api)
    {
        $data = $api->inquiry($id);

        return view('inquiries.show', [
            'inquiry' => $data['inquiry'] ?? [],
            'user' => session('enox_user'),
        ]);
    }

    public function updateStatus(int $id, Request $request, EnoxApiClient $api)
    {
        $request->validate([
            'status' => ['required', 'in:pending,in_progress,resolved,rejected'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $api->updateInquiryStatus($id, $request->only(['status', 'note']));

        return back()->with('status', 'Inquiry status updated.');
    }
}
