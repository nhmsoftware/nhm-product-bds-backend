<?php

namespace App\Modules\Project\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Project',
    title: 'Project',
    required: ['id', 'name', 'location', 'price', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        new OA\Property(property: 'name', type: 'string', example: 'Vinhomes Grand Park'),
        new OA\Property(property: 'keywords', type: 'object', example: ['căn hộ', 'quận 9']),
        new OA\Property(property: 'location', type: 'string', example: 'Quận 9, TP. HCM'),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/image.jpg'),
        new OA\Property(property: 'banner', type: 'string', example: 'https://example.com/banner.jpg'),
        new OA\Property(property: 'price', type: 'string', example: '3500000000'),
        new OA\Property(property: 'status', type: 'string', example: 'opening'),
        new OA\Property(property: 'type', type: 'string', example: 'apartment'),
        new OA\Property(property: 'is_public', type: 'boolean', example: true),
        new OA\Property(property: 'description', type: 'string', example: 'Dự án căn hộ cao cấp...'),
        new OA\Property(property: 'amenities', type: 'object', example: ['hồ bơi', 'công viên']),
        new OA\Property(property: 'floor_plans', type: 'object', example: ['mặt bằng tầng 1', 'mặt bằng tầng 2']),
        new OA\Property(property: 'legal_info', type: 'object', example: ['sổ hồng', 'giấy phép xây dựng']),
        new OA\Property(property: 'brochure', type: 'string', example: 'https://example.com/brochure.pdf'),
        new OA\Property(property: 'contact_info', type: 'object', example: ['hotline: 1900xxxx', 'email: contact@example.com']),
        new OA\Property(property: 'google_maps_url', type: 'string', example: 'https://maps.google.com/...'),
        new OA\Property(property: 'planning_info', type: 'object', example: ['quy hoạch 1/500']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class Project extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'name',
        'keywords',
        'location',
        'image',
        'banner',
        'price',
        'status',
        'type',
        'is_public',
        'description',
        'amenities',
        'floor_plans',
        'legal_info',
        'brochure',
        'contact_info',
        'google_maps_url',
        'planning_info',
    ];

    protected $casts = [
        'price' => 'string',
        'is_public' => 'boolean',
        'keywords' => 'array',
        'amenities' => 'array',
        'floor_plans' => 'array',
        'legal_info' => 'array',
        'contact_info' => 'array',
        'planning_info' => 'array',
    ];
}
