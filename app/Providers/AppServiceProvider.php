<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    
    public function register()
    {
            }

    
    public function boot()
    {
        if (env('HTTPS',false)) {
            URL::forceScheme('https');
        }
    }
}
