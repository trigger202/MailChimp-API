<?php

namespace App\Providers;

use App\testCounter;
use Illuminate\Support\ServiceProvider;
use App\APIClient;
use Illuminate\Support\Facades\Schema;


class AppServiceProvider extends ServiceProvider
{
    protected $defer = true;
    /**
     * Register any application services.
     *
     * @return void
     */

    public function boot()
    {
        Schema::defaultStringLength(191);
    }

    public function register()
    {
        return $this->app->singleton('apiclient', function($app)
        {
            if($app["App\APIClient"])
            {
                return $app["App\APIClient"];
            }
            return new \App\APIClient();

        });

    }

}
