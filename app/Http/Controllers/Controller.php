<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="API Documentation",
 *     version="1.0.0",
 *     description="مستندات API سیستم",
 *
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Production Server"
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api",
 *     description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="وارد کردن توکن JWT برای احراز هویت"
 * )
 */
abstract class Controller
{
    //
}
