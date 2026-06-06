<?php

namespace DrPshtiwan\LivewireAsyncSelect\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class InternalAuthToken
{
    protected static function getCurrentSecret(): string
    {
        $secret = config('async-select.internal.secret');

        if (empty($secret)) {
            throw new \RuntimeException('Internal auth secret is not configured. Set ASYNC_SELECT_INTERNAL_SECRET in your .env file.');
        }

        return $secret;
    }

    protected static function getValidSecrets(): array
    {
        $secrets = [];

        $current = config('async-select.internal.secret');
        if (! empty($current)) {
            $secrets[] = $current;
        }

        $previous = config('async-select.internal.previous_secret');
        if (! empty($previous)) {
            $secrets[] = $previous;
        }

        if (empty($secrets)) {
            throw new \RuntimeException('Internal auth secret is not configured.');
        }

        return $secrets;
    }

    public static function issue(int|string $userId, array $extra = []): string
    {
        $payload = array_merge([
            'uid' => (string) $userId,
            'exp' => time() + 60,
            'nonce' => (string) Str::uuid(),
            'm' => $extra['m'] ?? 'GET',
            'p' => $extra['p'] ?? '/',
            'h' => $extra['h'] ?? null,
            'bh' => $extra['bh'] ?? null,
            'perms' => $extra['perms'] ?? [],
        ], $extra);

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $secret = static::getCurrentSecret();

        $sig = hash_hmac('sha256', $json, $secret, true);
        $p64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $s64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        return $p64.'.'.$s64;
    }

    public static function verify(string $token): array
    {
        if (! str_contains($token, '.')) {
            throw new \RuntimeException('Malformed token');
        }

        [$p64, $s64] = explode('.', $token, 2);
        $payloadJson = base64_decode(strtr($p64, '-_', '+/'), true);
        $sig = base64_decode(strtr($s64, '-_', '+/'), true);

        if ($payloadJson === false || $sig === false) {
            throw new \RuntimeException('Corrupt token');
        }

        $secrets = static::getValidSecrets();
        $valid = false;

        foreach ($secrets as $secret) {
            $calc = hash_hmac('sha256', $payloadJson, $secret, true);
            if (hash_equals($calc, $sig)) {
                $valid = true;
                break;
            }
        }

        if (! $valid) {
            throw new \RuntimeException('Invalid signature');
        }

        $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);

        if (time() > (int) ($payload['exp'] ?? 0)) {
            throw new \RuntimeException('Expired token');
        }

        $nonce = $payload['nonce'] ?? null;
        if (empty($nonce)) {
            throw new \RuntimeException('Missing nonce');
        }

        $nonceKey = 'async-select:internal:nonce:'.$nonce;
        $nonceTtl = (int) config('async-select.internal.nonce_ttl', 120);

        // Remember the nonce at least until the token itself expires; otherwise a
        // token could be replayed during the gap between nonce eviction and token
        // expiry if nonce_ttl is configured shorter than the token lifetime.
        $remainingLife = (int) ($payload['exp'] ?? 0) - time() + 1;
        $ttl = max($nonceTtl, $remainingLife);

        if (Cache::add($nonceKey, 1, now()->addSeconds($ttl)) === false) {
            throw new \RuntimeException('Replay detected');
        }

        return $payload;
    }
}
