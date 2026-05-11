<?php

namespace App\Core\Controller;

use App\Core\Traits\HandleApi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API documentation for NHM Product Dat Xe Backend',
    title: 'NHM Product Dat Xe Backend API'
)]
#[OA\Server(
    url: '',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    description: "Enter 'Bearer ' followed by your token",
    name: 'Authorization',
    in: 'header'
)]
abstract class BaseController
{
    use AuthorizesRequests, HandleApi,  ValidatesRequests;
}
