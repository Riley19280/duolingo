<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * https://github.com/NativePHP/mobile-air/issues/96
 */
class NativeBridgePostFixMiddleware
{
    /**
     * NativePHP body-capture workaround.
     *
     * NativePHP's JS bridge double-captures POST bodies, generates two req-IDs,
     * combines them in the header, then looks up the body under the combined
     * string — which never matches. PHP receives an empty body.
     *
     * The JS layer encodes the payload as JSON in the X-Body header instead.
     * This middleware decodes it and merges the data into the request so that
     * $request->input() and $request->validate() work normally.
     */
    public function handle(Request $request, Closure $next)
    {
        $raw = $request->header('X-Body');
        if ($raw) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                $request->merge($data);
            }
        }

        return $next($request);
    }
}
