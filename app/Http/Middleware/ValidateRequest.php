<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ValidateRequest
{
    protected $app_id;

    protected $app_secret;

    public function __construct()
    {
        $this->app_id = env('APP_ID');
        $this->app_secret = env('APP_SECRET');
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Pre-Middleware Action

        $request_app_id = $request->header('service-id');

        if(! $this->app_id || ! $request_app_id) {
            return response()->json(['status' => false, 'message' => 'Service Unavailable'], HttpResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        if($this->app_id !== $request_app_id) {
            return response()->json(['status' => false, 'message' => 'Access Denied'], HttpResponse::HTTP_FORBIDDEN);
        }

        $response = $next($request);

        // Post-Middleware Action

        return $response;
    }
}
