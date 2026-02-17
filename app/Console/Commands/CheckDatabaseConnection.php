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

        $this->info("\nChecking for Available ODBC Drivers...");

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->warn("Windows detected. Listing drivers via registry...");
            // Simple check for Windows
            $this->line("Please check ODBC Data Source Administrator (64-bit) for installed drivers.");
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
        try {
            $pass = env('TLS_SQL_PASS');
            new \PDO("odbc:$dsn", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            $this->info("SUCCESS: Connected to database!");
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
