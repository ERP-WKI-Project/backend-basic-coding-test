<?php

namespace App\Http\Controllers\Machine;

use App\Http\Controllers\Controller;
use App\Http\Resources\Machine\ProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Get Machine User Profile
     *
     * Retrieve the authenticated machine user's profile information.
     *
     * @tag Machine Profile
     */
    public function __invoke(Request $request)
    {
        return ProfileResource::make($request->user());
    }
}
