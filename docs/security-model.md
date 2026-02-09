# Security Model

## Security Objectives

- Prevent tenant enumeration
- Prevent procedure discovery
- Prevent schema leakage
- Prevent SQL error exposure
- Enforce strict isolation

Assume hostile internet traffic at all times.

---

## Default Deny Strategy

Only allowlisted procedures may execute.

Everything else returns:

    HTTP 400
    Invalid request.

Attackers must never learn what exists.

---

## Tenant Protection

Tenant names are sensitive infrastructure identifiers.

NEVER expose them.

Logging should hash tenants:

    substr(hash('sha256', $tenant), 0, 12)

---

## Rate Limiting

Apply throttle to procedure endpoints.

Example:

    throttle:30,1

429 responses must remain generic.

---

## Logging Rules

Log internally:

- correlation id
- exception class
- error message
- tenant hash

Never log:

- passwords
- raw credentials
- connection strings

---

## SQL Failure Handling

SQL errors are INTERNAL.

Convert them into:

    Invalid request.

Users must never see database failures.

---

## Allowlist Enforcement

Defined in:

    config/stored_procedures.php

Two scopes:

- tenant
- global

Anything not listed is rejected automatically.
