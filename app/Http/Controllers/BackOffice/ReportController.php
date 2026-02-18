<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ReportMachineActivityRequest;

class ReportController extends Controller
{
    public function userMachineActivity(ReportMachineActivityRequest $request)
    {
        $validated = $request->validated();
    }
}
