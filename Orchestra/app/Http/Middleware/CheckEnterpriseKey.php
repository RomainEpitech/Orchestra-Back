<?php

namespace App\Http\Middleware;

use App\Models\Enterprise;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEnterpriseKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $enterpriseKey = $request->header('Enterprise-Key');
        
        if (!$enterpriseKey) {
            return response()->json([
                'message' => 'Enterprise key is missing from headers'
            ], 401);
        }

        $enterprise = Enterprise::where('key', $enterpriseKey)->first();
        
        if (!$enterprise) {
            return response()->json([
                'message' => 'Invalid enterprise key'
            ], 403);
        }

        $request->merge(['enterprise' => $enterprise]);
        
        return $next($request);
    }
}