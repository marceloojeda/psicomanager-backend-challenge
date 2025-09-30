<?php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'PsicoManager API',
            ],
            'routes' => [
                /*
                 * Route for accessing api documentation interface
                */
                'api' => 'api/documentation',
            ],
            'paths' => [
                /*
                 * Edit to include full URL in UI for assets
                */
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),

                /*
                 * File name of the generated json documentation file
                */
                'docs_json' => 'api-docs.json',

                /*
                 * File name of the generated YAML documentation file
                */
                'docs_yaml' => 'api-docs.yaml',

                /*
                 * Set this to `json` or `yaml` to determine which one to generate
                */
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Absolute paths to directory containing the swagger definitions.
                */
                'annotations' => [
                    base_path('app'),
                ],

                /*
                 * Path to directory containing the swagger definitions.
                 */
                'docs' => storage_path('api-docs'),

                /*
                 * Path to directory where to export views
                 */
                'views' => base_path('resources/views/vendor/l5-swagger'),

                /*
                 * Edit to set the api's base path
                 */
                'base' => env('L5_SWAGGER_BASE_PATH', null),

                /*
                 * Edit to set path where swagger ui assets should be stored
                 */
                'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),

                /*
                 * Absolute path to directories that should be exclude from scanning
                 * @deprecated Please use `scanOptions.exclude`
                 * `scanOptions.exclude` overwrites this
                 */
                'excludes' => [],
            ],

            'scanOptions' => [
                /**
                 * analyser: defaults to \OpenApi\StaticAnalyser .
                 */
                'analyser' => null,

                /**
                 * analysis: defaults to a new \OpenApi\Analysis .
                 */
                'analysis' => null,

                /**
                 * Custom query path processors classes.
                 */
                'processors' => [
                    // new \App\SwaggerProcessors\SchemaQueryParameter(),
                ],

                /**
                 * pattern: defaults to null .
                 */
                'pattern' => null,

                /*
                 * Absolute paths to directories that should be exclude from scanning
                 * @note This option overwrites `paths.excludes`
                 */
                'exclude' => [],

                /*
                 * Allows to generate specs either for OpenAPI 3.0.0 or OpenAPI 3.1.0.
                 * By default the spec will be in version 3.0.0
                 */
                'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
            ],

            /*
             * API security definitions. Will be generated into documentation file.
            */
            'securityDefinitions' => [
                'securitySchemes' => [
                    /*
                     * Examples of Security schemes
                    */
                    /*
                    'api_key_security_example' => [ // Unique name of security
                        'type' => 'apiKey', // The type of the security scheme. Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'A short description for security scheme',
                        'name' => 'api_key', // The name of the header or query parameter to be used.
                        'in' => 'header', // The location of the API key. Valid values are "query" or "header".
                    ],
                    'oauth2_security_example' => [ // Unique name of security
                        'type' => 'oauth2', // The type of the security scheme. Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'A short description for oauth2 security scheme.',
                        'flow' => 'implicit', // The flow used by the OAuth2 security scheme. Valid values are "implicit", "password", "application" or "accessCode".
                        'authorizationUrl' => 'http://example.com/auth', // The authorization URL to be used for (implicit/accessCode)
                        //'tokenUrl' => 'http://example.com/auth' // The authorization URL to be used for (password/application/accessCode)
                        'scopes' => [
                            'read:projects' => 'read your projects',
                            'write:projects' => 'modify projects in your account',
                        ]
                    ],
                    */

                    /* Open API 3.0 support
                    'passport' => [ // Unique name of security
                        'type' => 'oauth2', // The type of the security scheme. Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'Laravel passport oauth2 security.',
                        'in' => 'header',
                        'scheme' => 'https',
                        'flows' => [
                            "password" => [
                                "authorizationUrl" => config('app.url') . '/oauth/authorize',
                                "tokenUrl" => config('app.url') . '/oauth/token',
                                "refreshUrl" => config('app.url') . '/token/refresh',
                                "scopes" => []
                            ],
                        ],
                    ],
                    'sanctum' => [ // Unique name of security
                        'type' => 'apiKey', // Valid values are "basic", "apiKey" or "oauth2".
                        'description' => 'Enter token in format (Bearer <token>)',
                        'name' => 'Authorization', // The name of the header or query parameter to be used.
                        'in' => 'header', // The location of the API key. Valid values are "query" or "header".
                    ],
                    */
                ],
                'security' => [
                    /*
                     * Examples of Securities
                    */
                    [
                        /*
                        'oauth2_security_example' => [
                            'read',
                            'write'
                        ],

                        'passport' => []
                        */
                    ],
                ],
            ],
        ],
    ],
    'defaults' => [
        'routes' => [
            /*
             * Route for accessing parsed swagger annotations.
            */
            'docs' => 'docs',

            /*
             * Route for Oauth2 authentication callback.
            */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Middleware allows to prevent unexpected access to API documentation
            */
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],

            /*
             * Route Group options
            */
            'group_options' => [],
        ],

        'paths' => [
            /*
             * Absolute path to location where parsed annotations will be stored
            */
            'docs' => storage_path('api-docs'),

            /*
             * Absolute path to directory where to export views
            */
            'views' => resource_path('views/vendor/l5-swagger'),

            /*
             * Edit to set the api's base path
            */
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            /*
             * Edit to set path where swagger ui assets should be stored
            */
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),

            /*
             * Absolute path to directories that should be exclude from scanning
            */
            'excludes' => [],
        ],

        'scanOptions' => [
            'default_processors_configuration' => [
                // 'operationId.hash' => true,
            ],

            'processors' => [
                // new \App\SwaggerProcessors\SchemaQueryParameter(),
            ],

            'pattern' => null,

            'exclude' => [
                //
            ],

            'open_api_spec_version' => \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION,
        ],

        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url' => null,
        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],

            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),
            ],
        ],
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8888'),
        ],
    ],
];