<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Support\ApiResponse;

class LocationController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok(Location::orderBy('name')->get(['id', 'name']));
    }
}