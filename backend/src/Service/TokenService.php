<?php
declare(strict_types=1);

namespace App\Service;

class TokenService
{
    public function __construct(private readonly string $secret)
    {
    }

    /**
     * @param array<string, mixed> $identity
     */
    public function issue(array $identity, int $ttlHours = 12): string
    {
        $payload = [
            'sub' => $identity['id'] ?? null,
            'email' => $identity['email'] ?? null,
            'role' => $identity['role'] ?? null,
            'name' => $identity['full_name'] ?? null,
            'exp' => time() + ($ttlHours * 3600),
        ];

        $encodedPayload = $this->base64UrlEncode((string)json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->sign($encodedPayload);

        return $encodedPayload . '.' . $signature;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$encodedPayload, $signature] = $parts;
        if (!hash_equals($this->sign($encodedPayload), $signature)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload) || ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private function sign(string $encodedPayload): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $encodedPayload, $this->secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return (string)base64_decode(strtr($value, '-_', '+/'), true);
    }
}
