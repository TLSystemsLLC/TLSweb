<?php

namespace App\Database;

use App\Database\Exceptions\InvalidCredentialsException;
use App\Database\Exceptions\InvalidRequestException;

final class StoredProcedureGateway
{
    public function __construct(
        private readonly StoredProcedureClient $client
    ) {}

    /**
     * @return array{rc:int, rows:array<int, array<string,mixed>>}
     */
    public function call(?string $login, string $proc, array $params): array
    {
        $proc = trim($proc);
        if ($proc === '') {
            throw new InvalidRequestException('Invalid request.');
        }

        $allowTenant = (array) config('stored_procedures.tenant', []);
        $allowGlobal = (array) config('stored_procedures.global', []);

        // Determine scope WITHOUT leaking anything
        if (isset($allowTenant[$proc])) {
            $scope = 'tenant';
            $schema = $allowTenant[$proc];
        } elseif (isset($allowGlobal[$proc])) {
            $scope = 'global';
            $schema = $allowGlobal[$proc];
        } else {
            throw new InvalidRequestException('Invalid request.');
        }

        // Validate schema + params (count + types)
        $expected = $schema['params'] ?? [];
        if (!is_array($expected)) {
            throw new InvalidRequestException('Invalid request.');
        }
        if (count($params) !== count($expected)) {
            throw new InvalidRequestException('Invalid request.');
        }

        $typed = [];
        $i = 0;
        foreach ($expected as $name => $type) {
            $val = $params[$i] ?? null;

            switch ($type) {
                case 'int':
                    if (!is_numeric($val)) {
                        throw new InvalidRequestException('Invalid request.');
                    }
                    $typed[] = (int) $val;
                    break;

                case 'string':
                    $typed[] = (string) $val;
                    break;

                default:
                    throw new InvalidRequestException('Invalid request.');
            }

            $i++;
        }

        try {
            // Tenant only required for tenant scope
            if ($scope === 'tenant') {
                if ($login === null || trim($login) === '') {
                    throw new InvalidCredentialsException('Invalid credentials.');
                }

                try {
                    $tenant = \App\Support\Tenant::fromLogin($login);
                } catch (\Throwable) {
                    throw new InvalidCredentialsException('Invalid credentials.');
                }

                return $this->client->execWithReturnCode($tenant, $proc, $typed);
            }

            // Global scope runs in master
            return $this->client->execMasterWithReturnCode($proc, $typed);

        } catch (InvalidCredentialsException|InvalidRequestException $e) {
            // Preserve our intentionally vague exceptions
            throw $e;
        } catch (\Throwable $e) {
            // Any SQL/ODBC/runtime errors become a generic invalid request at the gateway boundary.
            // The route logs the real exception already.
            throw new InvalidRequestException('Invalid request.', 0, $e);
        }
    }
}
