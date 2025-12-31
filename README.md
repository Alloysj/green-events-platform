# CBK Green Events Governance & Disclosure Platform (Kenya)
A bank-grade, multi-tenant platform for sustainable event governance, evidence capture, and disclosure-ready reporting.

Status: Active development (multi-tenancy + Filament panels + event workflow + evidence locker + report exports in place).

---

## What is implemented now
### Platform foundations
- Database-per-tenant architecture with stancl/tenancy.
- System panel and tenant panel via Filament v3.
- RBAC (spatie/laravel-permission) with tenant and system roles.
- Audit logging for auth, role changes, approvals, and report exports.

### Event workflow (tenant)
- Status flow: Draft -> Pending Approval -> Approved -> Completed -> Report Published.
- Approvals: ESG, Procurement, and optional Risk/Compliance.
- Immutable status history per event.
- Filament actions: request approval, approve/reject, complete event, publish report.

### Evidence locker (tenant)
- File uploads stored on configured disk (local dev default; S3-compatible in prod).
- Metadata stored in tenant DB.
- Evidence links to Event, Vendor, ProcurementArtifact, WastePlan, and Report (with optional section tags).
- Signed download URLs with access control.

### Report generator v1 (tenant)
- JSON export schema.
- HTML rendering with evidence appendix.
- PDF export via external binary (configurable).
- Signed download links for exports.

---

## Panels and navigation
- System panel: `/system`
- Tenant panel: `/app`

When `APP_DOMAIN` is set, panels are domain-scoped:
- Tenant panel: `{tenant}.APP_DOMAIN/app`
- System panel: `system.APP_DOMAIN/system`

Local dev bypasses panel domain binding, but tenant resolution still uses the hostname. Example:
- Tenant: `bank-1.localhost` -> `http://bank-1.localhost:8000/app`
- System: `http://localhost:8000/system`

---

## Architecture overview
- System database: tenants, domains, system users, templates, system RBAC.
- Tenant databases: users, RBAC, audit logs, events, evidence, reports, exports.

---

## Tech stack
- Laravel 12
- Filament v3 + Livewire
- PostgreSQL
- stancl/tenancy (database-per-tenant)
- spatie/laravel-permission
- Storage: local or S3-compatible disks

---

## Configuration highlights
- `FILESYSTEM_DISK`: default storage disk
- `EVIDENCE_DISK`: evidence storage disk (defaults to `FILESYSTEM_DISK`)
- `REPORT_EXPORT_DISK`: report exports disk (defaults to `FILESYSTEM_DISK`)
- `REPORT_PDF_BINARY`: path to PDF binary (e.g., wkhtmltopdf)

---

## Development setup (local)
```bash
composer install
cp .env.example .env
php artisan key:generate
```

Optional but common local steps:
```bash
php artisan optimize:clear
php artisan system:migrate
php artisan tenant:provision --id=bank-1 --domain=bank-1.localhost --admin-name="Admin" --admin-email="admin@bank-1.test" --admin-password="secret"
```

---

## Tests
Feature tests cover provisioning, tenancy switching, RBAC, audit logging, event workflow, evidence access, and report exports.

Run:
```bash
php artisan test
```

---

## Documentation
- `docs/milestone-1.md`
- `docs/milestone-2.md`
