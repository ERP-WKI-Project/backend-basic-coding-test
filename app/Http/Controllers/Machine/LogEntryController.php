<?php

namespace App\Http\Controllers\Machine;

use App\Http\Controllers\Controller;
use App\Http\Requests\Machine\LogEntryPostRequest;
use App\Services\LogEntryService;
use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    public function index(Request $request)
    {
        return LogEntryService::getAll($request);
    }

    public function store(LogEntryPostRequest $request)
    {
        return response()->json([
            'status' => true
        ]);
    }
}
