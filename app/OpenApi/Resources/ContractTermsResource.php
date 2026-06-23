<?php

namespace App\OpenApi\Resources;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ContractTermsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Contract terms retrieved successfully'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'language', type: 'string', example: 'en'),
                new OA\Property(property: 'terms', type: 'string', example: 'Welcome to our platform. By using our services, you agree to the following terms and conditions...'),
                new OA\Property(property: 'retrieved_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000Z')
            ]
        )
    ]
)]

#[OA\Schema(
    schema: 'AllContractTermsResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'All contract terms retrieved successfully'),
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'terms_en', type: 'string', example: 'Welcome to our platform. By using our services, you agree to the following terms and conditions...'),
                new OA\Property(property: 'terms_ar', type: 'string', example: 'مرحباً بكم في منصتنا. باستخدام خدماتنا، فإنكم توافقون على الشروط والأحكام التالية...'),
                new OA\Property(property: 'retrieved_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000Z')
            ]
        )
    ]
)]

#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Error message'),
        new OA\Property(property: 'data', type: 'null', example: null),
        new OA\Property(property: 'error', type: 'string', example: 'Detailed error message (only in debug mode)')
    ]
)]

class ContractTermsResource
{
    // This class is used for OpenAPI documentation only
}
