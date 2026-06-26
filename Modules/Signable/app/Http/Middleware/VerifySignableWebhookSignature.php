<?php

namespace Modules\Signable\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySignableWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * Verifies that the incoming webhook request has a valid HMAC signature
     * from Signable. This prevents forged webhook attacks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('signable.webhook.secret');

        // If no secret is configured, reject all webhook requests for security
        if (empty($secret)) {
            return response()->json([
                'message' => 'Webhook signature verification not configured.',
            ], 500);
        }

        $signature = $request->header('X-Signature');

        if (empty($signature)) {
            return response()->json([
                'message' => 'Missing webhook signature.',
            ], 401);
        }

        // Signable sends the signature as HMAC-SHA256 of the payload
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Use timing-safe comparison to prevent timing attacks
        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 403);
        }

        return $next($request);
    }
}
