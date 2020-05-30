<?php
namespace Arcaela\Providers;
use \Illuminate\Support\Facades\File;
use \Illuminate\Support\Facades\Artisan;
use \Illuminate\Support\ServiceProvider;
class Console extends ServiceProvider {
    /**
     * Register services.
     *
     * @return void
     */
    public function register() {}
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
        if ($this->app->runningInConsole()){
            include_once(__DIR__.'/../Console/Commands.php');
        }
    }
}
