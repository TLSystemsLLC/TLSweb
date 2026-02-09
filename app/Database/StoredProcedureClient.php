<?php

namespace App\Database;

use App\Database\Exceptions\InvalidCredentialsException;
use App\Database\Exceptions\InvalidRequestException;
use PDO;
use PDOException;

class StoredProcedureClient
{
    private PDO $pdo;

    public function __construct()
    {
        $dsn  = env('TLS_ODBC_DSN');
        $user = env('TLS_SQL_USER');
        $pass = env('TLS_SQL_PASS');

        if (!$dsn || !$user) {
            // server misconfig (never user’s fault)
            throw new \RuntimeException('Database configuration error.');
        }

        try {
            $this->pdo = new PDO("odbc:$dsn", $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // bubble up; route logs CID + message; caller gets generic
            throw new \RuntimeException("ODBC connect failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array{rc:int, rows:array<int, array<string,mixed>>}
     */
    public function execWithReturnCode(string $tenantDb, string $procedure, array $params = []): array
    {
        $tenantDb = strtolower(trim($tenantDb));
        $procedure = trim($procedure);

        // These are defense-in-depth; gateway should already have validated.
        if (!preg_match('/^[A-Za-z0-9_]{1,128}$/', $tenantDb)) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }
        if (!preg_match('/^[A-Za-z0-9_]+$/', $procedure)) {
            throw new InvalidRequestException('Invalid request.');
        }

        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        $sql = "
            SET NOCOUNT ON;
            DECLARE @rc int;
            EXEC @rc = [{$tenantDb}].dbo.{$procedure} " . ($placeholders ? $placeholders : "") . ";
            SELECT @rc AS rc;
        ";

        return $this->runAndCaptureRc($sql, $params);
    }

    /**
     * @return array{rc:int, rows:array<int, array<string,mixed>>}
     */
    public function execMasterWithReturnCode(string $procedure, array $params = []): array
    {
        $procedure = trim($procedure);

        if (!preg_match('/^[A-Za-z0-9_]+$/', $procedure)) {
            throw new InvalidRequestException('Invalid request.');
        }

        $placeholders = implode(', ', array_fill(0, count($params), '?'));

        $sql = "
            SET NOCOUNT ON;
            DECLARE @rc int;
            EXEC @rc = dbo.{$procedure} " . ($placeholders ? $placeholders : "") . ";
            SELECT @rc AS rc;
        ";

        return $this->runAndCaptureRc($sql, $params);
    }

    /**
     * @return array{rc:int, rows:array<int, array<string,mixed>>}
     */
    private function runAndCaptureRc(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($params));

        $rows = [];
        $rc = null;

        do {
            try {
                $rowset = $stmt->fetchAll();
            } catch (\PDOException $e) {
                // Skip if this rowset isn't fetchable
                continue;
            }

            if (empty($rowset)) {
                continue;
            }

            // Is this our Return Code rowset?
            if (count($rowset) === 1 && array_key_exists('rc', $rowset[0])) {
                $rc = (int)$rowset[0]['rc'];
            } else {
                // If it's not the RC, it's actual data rows
                $rows = array_merge($rows, $rowset);
            }
        } while ($stmt->nextRowset());

        if ($rc === null) {
            throw new \RuntimeException('Stored procedure did not return a return code.');
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
