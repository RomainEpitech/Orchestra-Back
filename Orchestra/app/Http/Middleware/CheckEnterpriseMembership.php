<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEnterpriseMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $enterprise = $request->enterprise;
        $user = $request->user();

        if (!$user || $user->enterprise_uuid !== $enterprise->uuid) {
            return response()->json([
                'message' => 'You are not a member of this enterprise'
            ], 403);
        }

        return $next($request);
    }
}