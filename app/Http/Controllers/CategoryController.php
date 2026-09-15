<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\ApiResponse;

class CategoryController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::ok(Category::orderBy('name')->get(['id', 'name']));
    }
}