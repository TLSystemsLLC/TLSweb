<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckDatabaseConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks available ODBC drivers and tests connection string';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Checking Database Configuration...");

        $dsn  = env('TLS_ODBC_DSN');
        $user = env('TLS_SQL_USER');

        $this->line("DSN: <info>$dsn</info>");
        $this->line("User: <info>$user</info>");

        $this->info("\nChecking PHP Extensions...");
        $extensions = ['pdo', 'pdo_odbc', 'pdo_sqlsrv', 'sqlsrv', 'odbc'];
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->line(" - <info>$ext</info>: Loaded");
            } else {
                $this->line(" - <error>$ext</error>: NOT LOADED");
            }
        }

        if (extension_loaded('pdo_sqlsrv') && !extension_loaded('pdo_odbc')) {
            $this->warn("\n[NOTE] You have 'pdo_sqlsrv' loaded, but this application is configured to use 'pdo_odbc'.");
            $this->warn("While both connect to SQL Server, they are different PHP extensions.");
        }

        $this->info("\nChecking for Available ODBC Drivers...");

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->warn("Windows detected. Attempting to list drivers from Registry...");

            try {
                // Command to list ODBC drivers from registry
                $cmd = 'reg query "HKEY_LOCAL_MACHINE\SOFTWARE\ODBC\ODBCINST.INI\ODBC Drivers"';
                exec($cmd, $output, $returnVar);

                if ($returnVar === 0) {
                    $found = false;
                    foreach ($output as $line) {
                        // Lines look like: "    ODBC Driver 18 for SQL Server    REG_SZ    Installed"
                        if (preg_match('/^\s+(.*?)\s+REG_SZ\s+Installed/i', $line, $matches)) {
                            $driverName = trim($matches[1]);
                            $this->line(" - <comment>$driverName</comment>");
                            $found = true;
                        }
                    }
                    if (!$found) {
                        $this->warn("No drivers found in registry key.");
                    }
                } else {
                    $this->error("Failed to query registry.");
                }
            } catch (\Exception $e) {
                $this->error("Error reading registry: " . $e->getMessage());
            }

            $this->line("\nIf the list above is empty, please check ODBC Data Source Administrator (64-bit) manually.");
        } else {
            // Linux / macOS (UnixODBC)
            $this->info("Searching for odbcinst.ini...");

            $paths = ['/etc/odbcinst.ini', '/usr/local/etc/odbcinst.ini', '/opt/homebrew/etc/odbcinst.ini'];
            $found = false;
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $this->line("Found: $path");
                    $content = file_get_contents($path);
                    preg_match_all('/^\[(.*?)\]/m', $content, $matches);
                    if (!empty($matches[1])) {
                        $this->line("Available Drivers in $path:");
                        foreach ($matches[1] as $driver) {
                            $this->line(" - <comment>$driver</comment>");
                        }
                        $found = true;
                    }
                }
            }

            if (!$found) {
                $this->warn("Could not find odbcinst.ini or no drivers listed. Try running 'odbcinst -q -d' in the terminal.");
                $output = shell_exec('odbcinst -q -d');
                if ($output) {
                    $this->line("Drivers from 'odbcinst -q -d':");
                    $this->line($output);
                }
            }
        }

        $this->info("\nTesting PDO ODBC Connection...");
        if (!extension_loaded('pdo_odbc')) {
            $this->error("ABORTING TEST: 'pdo_odbc' extension is not loaded in PHP.");
            $this->warn("\nSUGGESTION: You have the 'sqlsrv' extensions enabled, but NOT 'pdo_odbc'.");
            $this->warn("The code currently uses: new PDO(\"odbc:\$dsn\", ...)");
            $this->warn("To fix this, you MUST enable 'extension=pdo_odbc' in your php.ini.");
            $this->warn("\nIf you are using a standard PHP installation on Windows, find this line:");
            $this->line("    ;extension=pdo_odbc");
            $this->warn("And change it to (remove the semicolon):");
            $this->line("    extension=pdo_odbc");
            $this->warn("\nCurrent php.ini: " . php_ini_loaded_file());
            return;
        }

        try {
            $pass = env('TLS_SQL_PASS');
            $pdo = new \PDO("odbc:$dsn", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $this->info("SUCCESS: Connected to database!");

            $this->info("\nChecking Tenant Registry...");
            try {
                $tenants = \App\Support\TenantRegistry::allowedTenants();
                if (empty($tenants)) {
                    $this->error("No allowed tenants found in registry.");
                } else {
                    $this->line("Allowed Tenants: " . implode(', ', array_keys($tenants)));
                }
            } catch (\Exception $e) {
                $this->error("Error calling TenantRegistry: " . $e->getMessage());
            }

        } catch (\PDOException $e) {
            $this->error("FAILED: " . $e->getMessage());

            if (str_contains($e->getMessage(), 'could not find driver')) {
                $this->warn("\nSUGGESTION: The driver name in your DSN may be incorrect for this server.");
                $this->warn("Check if one of the drivers listed above should be used instead of 'ODBC Driver 18 for SQL Server'.");
                $this->warn("Common values: 'ODBC Driver 17 for SQL Server', 'ODBC Driver 13 for SQL Server'.");
            }
        }
    }
}
