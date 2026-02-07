<?php

namespace App\Database;

use PDO;
use PDOException;

final class StoredProcedureClient
{
    private PDO $pdo;

    public function __construct()
    {
        // Example: "Driver={ODBC Driver 18 for SQL Server};Server=TLS-SQL1,1433;Encrypt=yes;TrustServerCertificate=yes;"
        $dsn  = env('TLS_ODBC_DSN');
        $user = env('TLS_SQL_USER');
        $pass = env('TLS_SQL_PASS');

        if (!$dsn || !$user) {
            throw new \RuntimeException('Missing TLS_ODBC_DSN / TLS_SQL_USER in .env');
        }

        try {
            $this->pdo = new PDO("odbc:$dsn", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("ODBC connect failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute a stored procedure in a tenant database and capture the integer return code.
     * Assumes T-SQL return value pattern: EXEC @rc = [db].dbo.proc ?, ?
     *
     * @return array{rc:int, rows:array<int, array<string,mixed>>}
     */
    public function execWithReturnCode(string $tenantDb, string $procedure, array $params = []): array
    {
        $tenantDb = strtolower(trim($tenantDb));

// Allow SQL Server DB names like: master, test, tenant_01, etc.
// Disallow anything that could be used for injection.
        if (!preg_match('/^[A-Za-z0-9_]{1,128}$/', $tenantDb)) {
            throw new \InvalidArgumentException('Invalid credentials.');
        }
        if (!preg_match('/^[a-z0-9_]+$/i', $procedure)) {
            throw new \InvalidArgumentException("Invalid stored procedure name.");
        }

        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        $sql = "
            DECLARE @rc int;
            EXEC @rc = [{$tenantDb}].dbo.{$procedure} " . ($placeholders ? $placeholders : "") . ";
            SELECT @rc AS rc;
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($params));

        // Result set 1: procedure rows (if any)
        $rows = $stmt->fetchAll();

        // Result set 2: rc
        $rc = -1;
        if ($stmt->nextRowset()) {
            $rcRow = $stmt->fetch();
            if (is_array($rcRow) && array_key_exists('rc', $rcRow)) {
                $rc = (int)$rcRow['rc'];
            }
        }

        return [
            'rc' => $rc,
            'rows' => $this->stripColumns($rows, ['Logo'])
        ];

    }

    /**
     * Execute a stored procedure in the master/global database and capture return code.
     * Uses the default DB of the connection (typically master).
     *
     * @return array{rc:int, rows:array<int, array<string,mixed>>}
     */
    public function execMasterWithReturnCode(string $procedure, array $params = []): array
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $procedure)) {
            // don’t leak anything useful
            throw new \InvalidArgumentException("Invalid request.");
        }

        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        $sql = "
        DECLARE @rc int;
        EXEC @rc = dbo.{$procedure} " . ($placeholders ? $placeholders : "") . ";
        SELECT @rc AS rc;
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($params));

        $rows = $stmt->fetchAll();

        $rc = -1;
        if ($stmt->nextRowset()) {
            $rcRow = $stmt->fetch();
            if (is_array($rcRow) && array_key_exists('rc', $rcRow)) {
                $rc = (int)$rcRow['rc'];
            }
        }

        return [
            'rc'   => $rc,
            'rows' => $this->stripColumns($rows, ['Logo']),
        ];
    }

    private function stripColumns(array $rows, array $omit): array
    {
        if (empty($omit)) return $rows;

        $omitMap = array_fill_keys($omit, true);

        foreach ($rows as &$row) {
            foreach ($omitMap as $col => $_) {
                unset($row[$col]);
            }
        }
        return $rows;
    }
}

