<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Fast POS API Documentation",
 *     description="API documentation for Fast POS Backend System",
 *     @OA\Contact(
 *         email="support@fastpos.com",
 *         name="Fast POS Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Tag(
 *     name="POS",
 *     description="POS related endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Transactions",
 *     description="Transaction management endpoints"
 * )
 */
abstract class Controller
{
    //
}
