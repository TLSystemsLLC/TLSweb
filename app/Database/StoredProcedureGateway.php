<?php

namespace App\Database;

use App\Database\Exceptions\InvalidCredentialsException;
use App\Database\Exceptions\InvalidRequestException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class StoredProcedureGateway
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
            $scope  = 'tenant';
            $schema = $allowTenant[$proc];
        } elseif (isset($allowGlobal[$proc])) {
            $scope  = 'global';
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
                    // tighter than is_numeric(): disallow floats like "1.2"
                    if (is_int($val)) {
                        $typed[] = $val;
                    } elseif (is_string($val) && preg_match('/^-?\d+$/', $val)) {
                        $typed[] = (int) $val;
                    } elseif (is_float($val) && (int)$val == $val) {
                        $typed[] = (int) $val;
                    } else {
                        throw new InvalidRequestException('Invalid request.');
                    }
                    break;

                case 'string':
                    $typed[] = (string) $val;
                    break;

                default:
                    throw new InvalidRequestException('Invalid request.');
            }

            $i++;
        }

        // Tenant only required for tenant scope
        if ($scope === 'tenant') {
            if ($login === null || trim($login) === '') {
                throw new InvalidCredentialsException('Invalid credentials.');
            }

            try {
                $tenant = \App\Support\Tenant::fromLogin($login);
            } catch (ServiceUnavailableHttpException $e) {
                throw $e;
            } catch (\Throwable) {
                throw new InvalidCredentialsException('Invalid credentials.');
            }

            // IMPORTANT: let SQL/ODBC/runtime exceptions bubble to route (for logging + 500)
            return $this->client->execWithReturnCode($tenant, $proc, $typed);
        }

        // Global scope runs in master
        // IMPORTANT: let SQL/ODBC/runtime exceptions bubble to route (for logging + 500)
        return $this->client->execMasterWithReturnCode($proc, $typed);
    }
}
