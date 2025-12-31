# Milestone 1 - Multi-Tenancy + Filament + RBAC + Audit (Progress Summary)

Date: 2025-12-28

## Scope covered
- Database-per-tenant multi-tenancy with stancl/tenancy (central system DB + per-tenant DBs)
- System vs tenant routing, migrations, provisioning flow
- Filament split into System and Tenant panels
- RBAC scaffolding (roles) and tenant audit logging
- Feature tests for provisioning, tenant context switching, RBAC, and audit log

## Key packages
- stancl/tenancy ^3.9.1
- spatie/laravel-permission ^6.0
- filament/filament target v3.x (note: vendor currently shows v4 APIs; align as needed)

## Multi-tenancy architecture
- Central/system DB stores tenants, domains, CBK templates.
- Tenant DB per bank stores operational data (users, audit logs, etc.).
- Tenant identification via domain/subdomain.

### Tenancy config
- Added `config/tenancy.php` with bootstrappers and migration params.
- Added database managers mapping in `tenancy.database.managers`.
- Added tenant connection and template tenant connection config.

### Models
- `app/Models/Tenant.php` extends Stancl base tenant and uses `HasDatabase` + `HasDomains`.
- `app/Models/Domain.php` extends Stancl base domain.

### Migrations
System DB migrations (database/migrations/system):
- tenants
- domains
- cbk_templates
- system users / sessions / password reset tokens
- spatie permission tables (system)

Tenant DB migrations (database/migrations/tenant):
- users / sessions / password reset tokens
- spatie permission tables (tenant)
- audit_logs

### Provisioning
- `app/Services/TenantProvisioner.php` creates tenant record, domain, DB, runs tenant migrations, seeds admin user.
- Uses tenant internal key `tenancy_db_name` for db name.
- Enforces tenant migrations run on the `tenant` connection.

### Routes
- `routes/system.php` (no tenancy init)
- `routes/tenant.php` (tenancy init by domain/subdomain)
- `routes/web.php` includes both

### Artisan commands
- `system:migrate` in `routes/console.php`
- `tenant:provision` in `routes/console.php`

## Filament panels
- System panel provider: `app/Providers/Filament/SystemPanelProvider.php`
  - Path: /system
  - Optional domain: system.<APP_DOMAIN>
- Tenant panel provider: `app/Providers/Filament/TenantPanelProvider.php`
  - Path: /app
  - Optional domain: {tenant}.<APP_DOMAIN>
  - Tenancy middleware enforced

### Filament resources/pages
- System Tenants resource: `app/Filament/System/Resources/TenantResource.php`
  - Create page provisions tenant via `TenantProvisioner`.
- Tenant Dashboard page: `app/Filament/Tenant/Pages/Dashboard.php`

## RBAC
- Roles constants in `app/Support/Roles.php`.
- Spatie permission config in `config/permission.php`.
- `app/Models/User.php` uses `HasRoles`.
- System Tenants resource gated to system roles.

## Audit log
- Tenant audit table `audit_logs`.
- `app/Models/AuditLog.php`.
- `app/Services/AuditLogger.php`.
- Event listeners wired in `app/Providers/AppServiceProvider.php` for:
  - Login/Logout
  - Role assigned/removed
  - Approval recorded (event)
  - Report published (event)

## Tests added
- `tests/Feature/TenantProvisioningTest.php`
- `tests/Feature/TenantContextSwitchingTest.php`
- `tests/Feature/SystemRoleRestrictionTest.php`
- `tests/Feature/AuditLogTest.php`

## Known issues / follow-ups
- Some tenancy artisan commands differ by version; validate `tenancy:*` vs `tenants:*` commands.
- Ensure `tenancy.database.managers` and `template_tenant_connection` are correct for your DB driver.
- If migrations error with “table exists”, tenant DB may already have tables without migration rows; verify and insert missing migration records or reset tenant DB.
- Filament v3 vs v4 mismatch: adjust dependency and resource signatures to match actual installed version.

## Environment notes
- Project moved from OneDrive to `C:\Users\Alois\Documents\green-co` to avoid file locks.
- PostgreSQL PDO driver required for system and tenant DBs.


