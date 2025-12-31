<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Remove default welcome route
        \Illuminate\Support\Facades\Route::get('/', function () {
            return view('welcome');
        });
    }
}