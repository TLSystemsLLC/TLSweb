<?php

namespace App\Support;

use App\Database\Exceptions\InvalidRequestException;
use App\Database\StoredProcedureGateway;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

final class TenantRegistry
{
    /**
     * Cache tenants for 10 minutes to avoid hammering master.
     */
    private const CACHE_SECONDS = 600;

    private static ?array $testCache = null;

    /**
     * @return array<string,bool> map of tenantCode => true
     */
    public static function allowedTenants(): array
    {
        // Use a static variable for testing to avoid persistent side effects
        if (app()->environment('testing') && self::$testCache !== null) {
            return self::$testCache;
        }

        $cacheFile = storage_path('framework/cache/tenants.php');

        // Use a simple file cache (no Laravel DB/cache driver dependency)
        // In testing, we skip reading from disk if we want a fresh start
        if (!app()->environment('testing') && is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_SECONDS) {
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
        } catch (ServiceUnavailableHttpException $e) {
            // Re-throw to trigger maintenance mode
            throw $e;
        } catch (\Throwable) {
            // If other infra failure, fail closed
            return [];
        }

        // If master call fails (business rc), be safe: return empty allowlist
        if (($result['rc'] ?? -1) !== 0) {
            return [];
        }

        // Expect rows with a "tenant_id" column
        $map = [];
        foreach (($result['rows'] ?? []) as $row) {
            $name = trim((string) ($row['tenant_id'] ?? ''));
            if ($name !== '') {
                $map[$name] = true;
                $map[strtolower($name)] = true; // allow case-insensitive lookup but preserve original via keys
            }
        }

        // Ensure cache directory exists
        @mkdir(dirname($cacheFile), 0775, true);

        // Write as a PHP return file if not in testing to avoid side effects
        if (!app()->environment('testing')) {
            file_put_contents(
                $cacheFile,
                "<?php\nreturn " . var_export($map, true) . ";\n"
            );
        } else {
            // Keep it in memory for the rest of this test run
            self::$testCache = $map;
        }

        return $map;
    }

    public static function isAllowed(string $tenant): bool
    {
        $tenant = trim($tenant);
        $allowed = self::allowedTenants();
        return isset($allowed[$tenant]) || isset($allowed[strtolower($tenant)]);
    }

    /**
     * Clear the in-memory cache for tests.
     */
    public static function clearTestCache(): void
    {
        self::$testCache = null;
    }
}
