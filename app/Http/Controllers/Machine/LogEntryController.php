<?php

namespace App\Http\Controllers\Machine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogEntryController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'status' => true
        ]);
    }
}
