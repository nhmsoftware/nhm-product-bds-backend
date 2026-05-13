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
    use AuthorizesRequests, HandleApi, ValidatesRequests;

    /**
     * Phân giải ServiceReturn thành JsonResponse
     */
    protected function resolveServiceReturn(\App\Core\DTOs\ServiceReturn $serviceReturn): \Illuminate\Http\JsonResponse
    {
        if (!$serviceReturn->success) {
            return response()->json([
                'success' => false,
                'message' => $serviceReturn->message,
                'data' => $serviceReturn->data
            ], $serviceReturn->code);
        }

        return response()->json([
            'success' => true,
            'message' => $serviceReturn->message,
            'data' => $serviceReturn->data
        ], $serviceReturn->code);
    }
}
