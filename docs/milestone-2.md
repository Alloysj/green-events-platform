# Milestone 2 - Event Workflow + Evidence Locker + Report Generator (Detailed)

Date: 2025-12-30

## Scope covered
- Event lifecycle workflow with approvals, status transitions, and immutable history.
- Evidence locker with file uploads, metadata, linking to domain objects, and signed downloads.
- Report generator v1 (HTML -> PDF job + JSON export) with evidence appendix.
- Tenant/system navigation behaviors verified and adjusted for local development.

## Event lifecycle workflow
### Status flow
Draft -> Pending Approval -> Approved -> Completed -> Report Published

### Approvals
- ESG approval
- Procurement approval
- Risk/Compliance approval (optional per event)

### Implementation
- Event status stored on the event record.
- Approval records stored per event + type.
- Rejections move event back to Draft.
- Status history captured on every transition.
- Audit logs recorded on status changes and approvals.

### Roles and permissions
- Request approval: TenantAdmin, EventOwner
- Record approval: TenantAdmin or specific role per type
  - ESG approval: ESGLead
  - Procurement approval: Procurement
  - Risk approval: RiskReviewer
- Complete event: TenantAdmin, EventOwner
- Publish report: TenantAdmin, ESGLead

### Filament UI actions
- Request Approval
- Record Approval (approve or reject + notes)
- Complete Event
- Publish Report

## Evidence locker
### Storage
- Configurable disk via `EVIDENCE_DISK` (defaults to `FILESYSTEM_DISK`, local in dev).
- Files stored under `evidence/` on the configured disk.

### Metadata (tenant DB)
- Original filename
- Storage disk + path
- MIME type
- Size
- Uploaded by
- Metadata JSON

### Linking
Evidence can link to:
- Event
- Vendor
- ProcurementArtifact
- WastePlan
- Report (with optional report section tag)

### Access control
- Evidence access limited to tenant roles.
- Download uses signed routes; file access enforced at controller level.

## Report generator v1
### Exports
- JSON schema export
- PDF export via external binary (e.g., wkhtmltopdf)

### Evidence appendix
- Evidence linked to the report or its event is listed in the appendix.

### Export storage
- Configurable disk via `REPORT_EXPORT_DISK` (defaults to `FILESYSTEM_DISK`).
- Signed download links for report exports.

### Report fields
- Optional event association
- Title
- Status (draft/generated/published)
- Generated/published timestamps

## Navigation behavior (verified)
- System panel: `/system`
- Tenant panel: `/app`
- With `APP_DOMAIN`, panels are bound to subdomains.
- Local development bypasses domain binding for panels, but tenant resolution still relies on hostname.

## Database overview (brief)
### System database
Tables include:
- `tenants`
- `domains`
- `cbk_templates`
- system `users`, `sessions`, `password_reset_tokens`
- system role/permission tables

### Tenant databases
Core tables:
- `users`, `sessions`, `password_reset_tokens`
- tenant role/permission tables
- `audit_logs`

Event workflow tables:
- `events` (status, requires_risk_approval, name)
- `event_approvals`
- `event_status_histories`

Evidence tables:
- `evidence_artifacts`
- `evidence_links`

Reporting tables:
- `reports`
- `report_exports`

## Key files (reference)
- Event workflow: `app/Services/EventLifecycle.php`
- Event models: `app/Models/Domains/Events/Event.php`, `EventApproval.php`, `EventStatusHistory.php`
- Evidence models: `app/Models/Domains/Evidence/EvidenceArtifact.php`, `EvidenceLink.php`
- Evidence UI: `app/Filament/Tenant/Resources/EvidenceArtifactResource.php`
- Report generator: `app/Services/ReportGenerator.php`
- Report jobs: `app/Jobs/GenerateReportPdf.php`, `GenerateReportJson.php`
- Report UI: `app/Filament/Tenant/Resources/ReportResource.php`
- Download endpoints: `app/Http/Controllers/Tenant/EvidenceDownloadController.php`, `ReportExportDownloadController.php`

## Configuration additions
- `config/evidence.php`
- `config/reporting.php`

## Known gaps / follow-ups
- PDF export requires a configured binary (`REPORT_PDF_BINARY`).
- No custom Filament page yet for viewing approval history or evidence appendix per report.
- Report publishing workflow can be tightened to align with event completion policy.
