<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
//use Auth, JWTAuth, JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Auth;
use App\User;


class AccessTokenController extends Controller
{
    public function issueToken(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if($credentials['username'] === null || $credentials['password'] === null) {
            return response()->json(['error' => 'Invalid!']  + $request->all(), 401);
        }

        try {
            $user = User::where('site', $credentials['username'])->where('password', $credentials['password'])->first();

            if ($user === null || !$token = JWTAuth::fromUser($user)) {
                return response()->json(['error' => 'Invalid!'], 401);
            }

        } catch (JWTException $e) {
            return response()->json(['error' => 'Can\'t Provide Token'], 500);
        }

        return response()->json(compact('token'));
    }

    public function user()
    {
        $user = Auth::user();
        return response()->json(compact('user'));
    }

    public function refreshToken(Request $request)
    {
        $token = JWTAuth::refresh($request->input('token'));

        return response()->json([
           'token' => $token,
        ], 201);
    }
}
