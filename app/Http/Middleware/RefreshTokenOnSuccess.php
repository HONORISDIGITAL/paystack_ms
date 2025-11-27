<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenOnSuccess
{
    /**
     * Handle an incoming request.
     * Refreshes the token on successful requests to extend its expiry.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only refresh token if the request was successful and user is authenticated
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && Auth::check()) {
            $user = Auth::user();
            $currentToken = $user->currentAccessToken();
            
            if ($currentToken) {
                // Update the existing token's expiration time
                $expirationMinutes = (float) config('sanctum.expiration', 0.17);
                $newExpiration = now()->addMinutes($expirationMinutes);
                
                $currentToken->update([
                    'expires_at' => $newExpiration
                ]);
                
                // Add response headers to indicate token was refreshed
                $response->headers->set('X-Token-Refreshed', 'true');
                $response->headers->set('X-Token-Expires-At', $newExpiration->toISOString());
            }
        }

        return $response;
    }
}
