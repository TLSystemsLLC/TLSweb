# TLS Web Application

This project is a high-security, multi-tenant web application built with Laravel and SQL Server. It follows a strict "Database as the Core" architecture, where all business logic resides in SQL Server stored procedures.

## Core Architecture

The TLS platform is designed with a "Deny by Default" security posture and follows these non-negotiable principles:

### 1. Stored Procedures Only
Laravel acts strictly as a secure transport and UI layer. The application **never** queries tables directly or uses Eloquent ORM for business data. All data access is funneled through the `StoredProcedureGateway`.

### 2. Return Code (rc) Contract
Every stored procedure returns an integer return code (`rc`):
- `0`: Success
- `non-zero`: Business rule failure (mapped to appropriate API responses)

### 3. Multi-Tenant Isolation
The system uses a database-per-tenant isolation model. Tenants are resolved via the login format `tenant.username`. Cross-tenant data access is impossible by design at the gateway level.

## Project Structure

- `app/Database/`: Core execution layer (`StoredProcedureClient`) and enforcement layer (`StoredProcedureGateway`).
- `config/stored_procedures.php`: The allowlist for all permitted stored procedure calls, including parameter schemas.
- `app/Support/Tenant.php`: Multi-tenant resolution logic.
- `routes/api.php`: Thin API controllers that delegate to the Gateway.

## Getting Started

### Prerequisites
- PHP 8.2+
- SQL Server with ODBC Driver 17/18
- Composer
- Node.js & NPM (for Bootstrap/Vite)

### Environment Setup
1. Copy `.env.example` to `.env`.
2. Configure the following TLS-specific variables:
   ```env
   TLS_ODBC_DSN="Driver={ODBC Driver 18 for SQL Server};Server=your_server;Database=master;TrustServerCertificate=yes;"
   TLS_SQL_USER="your_user"
   TLS_SQL_PASS="your_pass"
   ```

### Installation
```bash
composer install
npm install && npm run build
php artisan key:generate
```

## Development Guidelines

Strict adherence to `ARCHITECTURE.md` and `AI_RULES.md` is mandatory.

### Exposing a New Stored Procedure
1.  **Verify Signature**: Check the procedure parameters in SQL Server.
2.  **Allowlist**: Add the procedure to the `tenant` or `global` array in `config/stored_procedures.php`.
3.  **Define Schema**: Specify parameter names and types (`int`, `string`) in the config.
4.  **Test**: Add a feature test in `tests/Feature/` to verify the execution and return code handling.

## Testing
Run the full test suite to ensure architectural compliance:
```bash
php artisan test
```

---
Refer to `ARCHITECTURE.md` for detailed technical specifications.
