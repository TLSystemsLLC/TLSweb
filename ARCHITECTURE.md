# TLS Web Application Architecture

## Purpose
This document defines the non‑negotiable architectural rules for the TLS web platform.
It exists to prevent architectural drift — especially when AI is generating code.

The SQL Server database is the application core.
Laravel is the secure transport and UI layer.

---

## Core Principles

### 1. Stored Procedures Only
The application NEVER:

- Queries tables directly
- Uses ORM models
- Uses query builders for business data

ALL data access must go through approved stored procedures.

Violation of this rule is considered a **critical architecture failure**.

---

### 2. Return Code Contract

Every stored procedure returns an integer return code (`rc`).

| rc | Meaning |
|------|------------|
| 0 | Success |
| non‑zero | Business rule failure |

Return codes are NOT exceptions.

SQL errors are infrastructure failures and must never be exposed externally.

---

### 3. Multi‑Tenant Isolation

Each customer lives in its own SQL Server database.

Login format:

    tenant.username

Example:

    mrwr.tlyle

Rules:

- Tenant procedures execute ONLY inside the tenant DB.
- Global procedures execute ONLY in master.
- Cross‑tenant access is impossible by design.

---

## System Layers

### API Layer (Controllers)
Responsibilities:

- Validate request shape
- Enforce throttling
- Call StoredProcedureGateway

Controllers must remain THIN.

No business logic allowed.

---

### StoredProcedureGateway (Enforcement Layer)

This is the most important class in the system.

Responsibilities:

- Enforce stored procedure allowlist
- Determine tenant vs global scope
- Validate parameter count
- Validate parameter types
- Resolve tenant from login
- Block unapproved procedures

If a procedure is not allowlisted → reject immediately.

Default posture: **DENY**.

---

### StoredProcedureClient (Execution Layer)

Responsibilities:

- Own the PDO ODBC connection
- Execute procedures
- Capture return code
- Return result sets
- Strip sensitive columns

This layer knows NOTHING about business rules.

---

## Security Posture

The system must NEVER leak:

- tenant names
- database names
- stored procedure names
- schema
- stack traces
- SQL errors

### Error Mapping

| Scenario | Response |
|------------|--------------|
| Invalid credentials | 401 |
| Invalid request | 400 |
| Rate limited | 429 |
| Business failure | 422 |

All invalid requests return:

    Invalid request.

No hints.
No details.

---

## API Response Shape

Success:

{
  "rc": 0,
  "ok": true,
  "data": [],
  "error": null
}

Business failure:

{
  "rc": 99,
  "ok": false,
  "data": [],
  "error": { "code": 99 }
}

---

## Development Workflow

When exposing a new stored procedure:

1. Add it to `config/stored_procedures.php`
2. Define parameter schema
3. Call ONLY via gateway
4. Add a feature test
5. Confirm no data leakage

---

## Architectural Philosophy

Laravel exists to provide:

- transport
- authentication
- validation
- session management
- UI

NOT business rules.

If business logic appears in Laravel, the architecture is being violated.
