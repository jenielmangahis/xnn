<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $monolog = \Log::getMonolog();
        $monolog->pushHandler(new \Monolog\Handler\SlackWebhookHandler(
            "https://hooks.slack.com/services/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",
            "logs",
            config("app.name"),
            true,
            null,
            false,
            false,
            \Monolog\Logger::ERROR,
            true
        ));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
