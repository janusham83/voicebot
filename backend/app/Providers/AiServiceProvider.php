<?php

namespace App\Providers;

use App\Services\AiServiceFactory;
use App\Services\GeminiService;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiServiceFactory::class, function ($app) {
            return new AiServiceFactory(
                $app->make(GeminiService::class)
            );
        });
    }
}