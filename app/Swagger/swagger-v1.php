<?php

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Corenet Tech Fast POS API",
 *     description="Corenet Tech Fast POS System",
 *     @OA\Contact(
 *         email="support@fastpos.com",
 *         name="Corenet Tech Fast POS Support"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * 
 * @OA\Server(
 *     description="Fast POS API Server",
 *     url=L5_SWAGGER_CONST_HOST
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 *
 * @OA\Tag(
 *     name="Company Registration",
 *     description="All endpoints required to register a company (user, verification, merchant profile, and reference data)."
 * )
 */

