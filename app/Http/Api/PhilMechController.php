<?php

namespace App\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhilMechController extends BaseController
{

    public function createToken(Request $request)
{
    $secret = $request->get('secret');

    if ($secret === env('SECRET_DEX')) {
        $email = env('SECRET_EMAIL');
        $password = env('SECRET_PASS');

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->user()->tokens()->delete();

        $token = $request->user()->createToken('philmech-token')->plainTextToken;

        return $this->sendResponse($token, 'Your Token');
    }

    return $this->sendError('Wag po.', [], 422);
}
}
