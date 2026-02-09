<?php

namespace App\Support;

use App\Database\StoredProcedureClient;

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

        // Call master..getTenants (no tenant DB prefix)
        $sp = new StoredProcedureClient();
        $result = $sp->execMasterWithReturnCode('getTenants', []);

        // If master call fails, be safe: return empty allowlist
        if (($result['rc'] ?? -1) !== 0) {
            return [];
        }

        // Expect rows with a "name" column (per your note)
        $map = [];
        foreach (($result['rows'] ?? []) as $row) {
            $name = strtolower(trim((string)($row['name'] ?? '')));
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
