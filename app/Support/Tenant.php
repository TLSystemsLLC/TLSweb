<?php

namespace App\Support;

final class Tenant
{
    /**
     * Extract tenant database code from a login like "test.tlyle".
     */
    public static function fromLogin(string $login): string
    {
        $login = trim($login);

        // Expect "tenant.user" (only split on first dot)
        $parts = explode('.', $login, 2);
        $code = strtolower(trim($parts[0] ?? ''));

        if ($code === '') {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        if (!TenantRegistry::isAllowed($code)) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }

        return $code;
    }
}
