<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(title: 'XConnect API', version: '1.0.0')]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Use Sanctum bearer token from /api/login'
)]
abstract class Controller
{
    // Base API controller
}
