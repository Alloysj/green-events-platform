# Vendor Governance (SRS FR-031) - Implementation Summary

Date: 2025-12-31

## Purpose
This document summarizes the tenant-scoped implementation of Approved Vendor Lists with expiry/reverification cycles (SRS FR-031). It describes schema, model behavior, Filament UI changes, RBAC, audit logging, and tests so reviewers can validate correctness and tenancy isolation.

---

## Schema changes (tenant DB only)
- New table: `vendor_approvals` (migration: `database/migrations/tenant/2025_12_31_000001_create_vendor_approvals_table.php`)
  - Columns: `id`, `vendor_id` (FK -> `vendors`), `status` (approved|revoked|pending), `approved_by` (nullable FK -> users), `approved_at` (nullable timestamp), `expires_at` (nullable timestamp), `notes` (nullable text), `created_at`, `updated_at`.
  - **Indexes**: Postgres-friendly indexes were added for `status`, `expires_at`, and a composite index on `(vendor_id, status)` to support common lookups and filters.
- Existing tenant tables modified earlier for vendors and vendor_categories (see earlier migration files).

> All migration files are in `database/migrations/tenant` and run per-tenant (no system DB changes).

---

## Models & behavior
- New Eloquent model: `App\Models\Domains\Vendors\VendorApproval` (tenant-scoped).
- Extended `App\Models\Domains\Vendors\Vendor`:
  - relations: `approvals()` (hasMany), `latestApproval()`
  - helpers: `isApproved()`, `isExpired()`, `needsReverification()` (uses `config('vendors.reverification_days')`)
  - actions: `approveBy($actor, $expiresAt, $notes)` and `revokeBy($actor, $notes)`. These methods:
    - enforce permission checks (`VENDORS:APPROVE`) or TenantAdmin role
    - create `vendor_approvals` records
    - call `AuditLogger::log()` with events `vendor.approved` / `vendor.revoked` and relevant metadata

Notes:
- Slug generation for vendor categories is unchanged; vendor approval logic is independent and tenant-local.

---

## Filament tenant UI changes
File: `app/Filament/Tenant/Resources/VendorResource.php`
- Table: new badge column `Approval` showing one of: `Approved`, `Expired`, `Needs Reverification`, `Not Approved`.
- Filters: `Approved`, `Expired`, `Needs Reverification`.
- Actions (table row actions): `Approve` (form: `expires_at`, `notes`) and `Revoke` (form: `notes`).
  - Visibility requires either `VENDORS:APPROVE` permission or `TenantAdmin` role (consistent with other resources).

UI notes for reviewers:
- Approve action writes a `vendor_approvals` row with `status = approved` and `expires_at` when supplied.
- Revoke action writes a `vendor_approvals` row with `status = revoked`.
- No system-panel vendor approval UI was added (tenant-only constraint maintained).

---

## RBAC
- New permission added and seeded for tenants in `app/Services/TenantProvisioner.php`:
  - `VENDORS:APPROVE` (granted to default `TenantAdmin` role during provisioning)
- Filament access patterns follow repo conventions:
  - If permissions table is empty (legacy), resources default to tenant roles.
  - When permissions exist, `VENDORS:READ`, `VENDORS:WRITE`, `VENDORS:APPROVE` are enforced for read/write/approval.

---

## Audit logging
- Approval / revocation use the existing `AuditLogger` service which writes tenant-scoped `audit_logs` entries.
- Events emitted:
  - `vendor.approved` with metadata { vendor_id, expires_at, notes }
  - `vendor.revoked` with metadata { vendor_id, notes }
- These follow existing patterns used elsewhere in the tenant codebase (see `app/Services/AuditLogger.php` and other usage sites).

---

## Tests
New / updated tests (tenant feature tests):
- `tests/Feature/VendorApprovalTest.php`
  - Asserts approving a vendor creates a `vendor_approvals` row and an `audit_logs` entry.
  - Asserts expired approvals are not treated as approved (i.e. `isApproved()` returns false and `isExpired()` true).
  - Asserts that `VENDORS:APPROVE` permission / TenantAdmin role is required to approve/revoke.
- `tests/Feature/VendorsAccessTest.php` and other vendor tests updated to handle slug auto-generation and RBAC changes.
- `tests/Feature/VendorCategorySlugTest.php` added for slug uniqueness.

Tests run per-tenant using the project test helpers (they bootstrap a tenant, run tenant migrations and then run assertions).

---

## Configuration
- `config/vendors.php` added with key `reverification_days` (default 30) to control when a vendor is flagged for reverification.
- The `needsReverification()` helper uses this setting to flag vendors whose expiry is within the next `N` days.

**Postgres-specific notes:** the new migrations use Postgres-optimized types and indexes (e.g., JSONB with GIN indexes for compliance metadata and explicit indexes for approval lookups). The tenant database helper was simplified to rely on the configured DB manager (Postgres) and no longer uses filesystem-specific logic (sqlite file removal).

---

## Reviewer checklist 
- Run tenant migrations: `php artisan migrate --path=database/migrations/tenant --realpath` (or via provisioning flow). Ensure the new `vendor_approvals` table exists in tenant DBs.
- Boot a tenant (see existing test helper `bootstrapTenant`) and manually:
  - Create a Vendor in tenant panel and use Filament `Approve` action; verify `vendor_approvals` row and `audit_logs` entry were created.
  - Verify revoked approvals create a revoked `vendor_approvals` row and audit log entry.
  - Verify approval badge, filters, and expiry/reverification logic behave as expected.
- Confirm permissions:
  - With no permissions present, TenantAdmin (role) can approve.
  - When tenant permissions exist, only users with `VENDORS:APPROVE` (or TenantAdmin) can approve/revoke.
- Check tenancy isolation: approvals and audit logs appear in tenant DB only (no system DB writes for vendor approvals).

---

## Notes & follow-ups
- For UX: consider adding a default expiry suggestion (e.g., 12 months) and a confirmation email/workflow for expired vendors.
- Consider adding a `vendor_approval_runs` or scheduled job if automated re-verification is required in the future.
- All code and migrations added keep with existing repo patterns and are intentionally tenant-scoped.

---

If you'd like, I can also add a short PR description / diff summary for the reviewers and run the full test suite before creating a PR.
