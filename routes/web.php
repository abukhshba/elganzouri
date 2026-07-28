<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session(['locale' => $locale]);
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }
    return redirect()->back();
})->name('lang.switch');
