# AI RULES (Authoritative)

This file exists to control AI behavior when generating code.

AI MUST follow these rules without exception.

---

## Absolute Rules

### NEVER access tables directly
Use stored procedures only.

### NEVER introduce ORM models
Eloquent is forbidden for business data.

### NEVER duplicate business rules
Validation logic belongs in SQL Server.

Laravel validation is for UX only.

### NEVER bypass the StoredProcedureGateway
All data access flows through the gateway.

---

## Architecture Preservation

If AI is unsure how to implement something:

STOP.
Ask for clarification.

Do NOT invent patterns.

---

## Security Rules

AI must never generate code that:

- leaks tenant names
- exposes schema
- returns SQL errors
- reveals stored procedure names

---

## Controller Design

Controllers must remain thin.

Correct pattern:

Controller → Gateway → Client

Nothing else.

---

## When Adding Stored Procedures

AI must:

1. Add allowlist entry
2. Define parameter schema
3. Use gateway
4. Return rc contract

No shortcuts.

---

## If AI Violates These Rules

The code must be rejected.
Rewrite immediately.
