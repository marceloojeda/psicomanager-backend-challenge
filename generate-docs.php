<?php

require_once __DIR__ . '/vendor/autoload.php';

use OpenApi\Generator;

// Scan the app directory for annotations
$openapi = Generator::scan([__DIR__ . '/app']);

// Output to storage/api-docs/api-docs.json
file_put_contents(__DIR__ . '/storage/api-docs/api-docs.json', $openapi->toJson());

echo "Documentação OpenAPI gerada com sucesso em storage/api-docs/api-docs.json\n";