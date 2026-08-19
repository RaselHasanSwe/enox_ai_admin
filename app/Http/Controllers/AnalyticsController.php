<?php

namespace App\Http\Controllers;

class AnalyticsController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard', [], 301);
    }
}
