<?php

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Route::fallback(function (Request $request) {
    if ($request->is('api/*')) {
        return ApiResponse::fail('Not found.', 404);
    }

    $spa = public_path('index.html');

    if (! is_file($spa)) {
        throw new NotFoundHttpException;
    }

    return response()->file($spa);
});

