<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPolicy;
use Illuminate\Http\JsonResponse;

class PrivacyPolicyController extends Controller
{
    public function index(): JsonResponse
    {
        $policies = PrivacyPolicy::orderBy('order')->get();

        return response()->json([
            'data' => $policies,
        ]);
    }
}
