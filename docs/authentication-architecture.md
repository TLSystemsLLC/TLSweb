# Authentication Architecture

## Goal

Provide secure tenant-aware authentication without exposing infrastructure details.

---

## Login Format

    tenant.username
    password

Tenant is parsed first.

If tenant is invalid → return:

    Invalid credentials.

Do NOT reveal whether the tenant exists.

---

## Authentication Flow

1. Parse tenant from login.
2. Validate tenant via master stored procedure.
3. Execute authentication procedure inside tenant DB.
4. Establish session.

---

## Failure Responses

Always identical:

    Invalid credentials.

Never reveal:

- tenant validity
- username validity
- password validity

---

## Session Model

Use Laravel sessions.

Store:

- user id
- tenant
- permissions

Never store credentials.

---

## Future Recommendation (HIGHLY ADVISED)

Move toward:

- short-lived sessions
- optional MFA
- device tracking

But keep stored procedures as the identity authority.
