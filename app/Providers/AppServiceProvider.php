<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // Register Back Office API Documentation
        Scramble::registerApi('back-office', [
            'api_path' => 'api/backoffice',
            'info' => [
                'title' => 'Back Office API',
                'version' => config('app.api_version'),
                'description' => 'API Documentation for Back Office - Manage users, machines, shifts, and reports.',
            ],
        ])
        ->routes(fn (Route $route) => str_starts_with($route->uri, 'api/backoffice'))
        ->afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer', 'sanctum'));
        });

        // Register Machine API Documentation
        Scramble::registerApi('machine', [
            'api_path' => 'api/machine',
            'info' => [
                'title' => 'Machine API',
                'version' => config('app.api_version'),
                'description' => 'API Documentation for Machine - Authentication, profile, and activity logging.',
            ],
        ])
        ->routes(fn (Route $route) => str_starts_with($route->uri, 'api/machine'))
        ->afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer', 'sanctum'));
        });
    }
}
