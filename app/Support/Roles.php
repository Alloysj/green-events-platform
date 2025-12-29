<?php

namespace App\Support;

class Roles
{
    public const TENANT_ADMIN = 'TenantAdmin';
    public const ESG_LEAD = 'ESGLead';
    public const PROCUREMENT = 'Procurement';
    public const EVENT_OWNER = 'EventOwner';
    public const RISK_REVIEWER = 'RiskReviewer';
    public const AUDITOR = 'Auditor';

    public const SYSTEM_ADMIN = 'SystemAdmin';
    public const CBK_REVIEWER = 'CBKReviewer';

    public static function tenantRoles(): array
    {
        return [
            self::TENANT_ADMIN,
            self::ESG_LEAD,
            self::PROCUREMENT,
            self::EVENT_OWNER,
            self::RISK_REVIEWER,
            self::AUDITOR,
        ];
    }

    public static function systemRoles(): array
    {
        return [
            self::SYSTEM_ADMIN,
            self::CBK_REVIEWER,
        ];
    }
}
