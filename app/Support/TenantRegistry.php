<?php

namespace App\Support;

use App\Database\Exceptions\InvalidRequestException;
use App\Database\StoredProcedureGateway;

final class TenantRegistry
{
    /**
     * Cache tenants for 10 minutes to avoid hammering master.
     */
    private const CACHE_SECONDS = 600;

    /**
     * @return array<string,bool> map of tenantCode => true
     */
    public static function allowedTenants(): array
    {
        $cacheFile = storage_path('framework/cache/tenants.php');

        // Use a simple file cache (no Laravel DB/cache driver dependency)
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_SECONDS) {
            /** @var array $data */
            $data = include $cacheFile;
            if (is_array($data)) return $data;
        }

        // Call master-scoped stored procedure THROUGH the gateway (no bypass).
        // Global scope does not require login/tenant parsing.
        try {
            /** @var StoredProcedureGateway $gateway */
            $gateway = app(StoredProcedureGateway::class);

            $result = $gateway->call(null, 'getTenants', []);
        } catch (InvalidRequestException) {
            // If not allowlisted / schema mismatch, fail closed
            return [];
        } catch (\Throwable) {
            // If DB is unreachable or other infra failure, fail closed
            return [];
        }

        // If master call fails (business rc), be safe: return empty allowlist
        if (($result['rc'] ?? -1) !== 0) {
            return [];
        }

        // Expect rows with a "name" column
        $map = [];
        foreach (($result['rows'] ?? []) as $row) {
            $name = strtolower(trim((string) ($row['name'] ?? '')));
            if ($name !== '') {
                $map[$name] = true;
            }
        }

        // Ensure cache directory exists
        @mkdir(dirname($cacheFile), 0775, true);

        // Write as a PHP return file
        file_put_contents(
            $cacheFile,
            "<?php\nreturn " . var_export($map, true) . ";\n"
        );

        return $map;
    }

    public static function isAllowed(string $tenant): bool
    {
        $tenant = strtolower(trim($tenant));
        return isset(self::allowedTenants()[$tenant]);
    }
}
