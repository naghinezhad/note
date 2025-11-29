<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/images/{path}', function ($path) {

    $path = str_replace('..', '', $path);

    if (! Storage::disk('private')->exists($path)) {
        abort(404);
    }

    return response()->file(Storage::disk('private')->path($path));

})->where('path', '.*')
    ->middleware('signed')
    ->name('signed.file');
