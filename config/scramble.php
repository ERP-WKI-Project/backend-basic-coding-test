<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

return [
    'api_path' => 'api',
    'api_domain' => null,
    'export_path' => 'api.json',

    'info' => [
        'version' => config('app.api_version'),
        'description' => 'API Documentation for ERP Wiki',
    ],

    'ui' => [
        'title' => null,
        'theme' => 'system',
        'hide_try_it' => false,
        'hide_schemas' => false,
        'logo' => '',
        'try_it_credentials_policy' => 'include',
        'layout' => 'responsive',
    ],

    'servers' => null,
    'enum_cases_description_strategy' => 'description',
    'enum_cases_names_strategy' => false,
    'flatten_deep_query_parameters' => true,

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    'hooks' => [
        OpenApi::class => [
            function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'sanctum')
                );
            },
        ],
    ],
];
