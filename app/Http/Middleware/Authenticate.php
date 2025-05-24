<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        if (Request::is('api*')) {

            $response = [
                'statusCode' => 401,
                'data' => (object)[],
                'message' => "Unauthorized"
            ];
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode($response);
            die;
        } else {
            return route('login');
        }
    }
}
