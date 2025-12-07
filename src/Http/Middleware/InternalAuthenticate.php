<?php

namespace DrPshtiwan\LivewireAsyncSelect\Http\Middleware;

use Closure;
use DrPshtiwan\LivewireAsyncSelect\Support\InternalAuthToken;
use Illuminate\Auth\Middleware\Authenticate as CoreAuthenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InternalAuthenticate
{
    public function handle(Request $request, Closure $next, ...$params)
    {
        $guards = [];
        $persist = false;

        foreach ($params as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }

            if (strcasecmp($p, 'persist') === 0) {
                $persist = true;

                continue;
            }

            foreach (explode(',', $p) as $g) {
                $g = trim($g);
                if ($g !== '') {
                    $guards[] = $g;
                }
            }
        }

        if (empty($guards)) {
            $guards[] = config('auth.defaults.guard', 'web');
        }

        $hdr = $request->header('X-Internal-User')
            ?? $request->header('x-internal-user')
            ?? $request->header('X-INTERNAL-USER');

        if (! $hdr) {
            $authMw = app(CoreAuthenticate::class);

            return $authMw->handle($request, $next, ...$guards);
        }

        try {
            $payload = InternalAuthToken::verify($hdr);
        } catch (\Throwable $e) {
            abort(401, 'Internal auth failed: '.$e->getMessage());
        }

        if (isset($payload['m']) && strtoupper($payload['m']) !== $request->getMethod()) {
            abort(401, 'Method mismatch');
        }
        if (isset($payload['p']) && $payload['p'] !== $request->getPathInfo()) {
            abort(401, 'Path mismatch');
        }
        if (isset($payload['h'])) {
            $host = $request->getSchemeAndHttpHost();
            if ($payload['h'] !== $host) {
                abort(401, 'Host mismatch');
            }
        }
        if (! empty($payload['bh'])) {
            $raw = $request->getContent() ?? '';
            $bh = hash('sha256', $raw);
            if (! hash_equals($payload['bh'], $bh)) {
                abort(401, 'Body hash mismatch');
            }
        }

        $guard = $guards[0] ?? config('auth.defaults.guard', 'web');
        Auth::shouldUse($guard);

        if ($persist) {
            if (! $request->hasSession()) {
                abort(500, 'Cannot persist login without session; add "web" middleware.');
            }
            Auth::guard($guard)->loginUsingId($payload['uid']);
            $request->session()->migrate(true);
            $request->session()->regenerateToken();
        } else {
            Auth::guard($guard)->onceUsingId($payload['uid']);
        }

        $request->setUserResolver(fn () => Auth::guard($guard)->user());

        // Preserve locale from Accept-Language header
        $localeHeader = $request->header('Accept-Language');
        if ($localeHeader) {
            app()->setLocale($localeHeader);
        }

        if (! empty($payload['perms'])) {
            foreach ($payload['perms'] as $perm) {
                $user = Auth::guard($guard)->user();
                if ($user && method_exists($user, 'can')) {
                    $hasPermission = call_user_func([$user, 'can'], $perm);
                    if (! $hasPermission) {
                        abort(403, 'Forbidden (permission: '.$perm.')');
                    }
                }
            }
        }

        return $next($request);
    }
}
