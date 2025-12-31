<?php

namespace App\Filament\System\Pages;

use Filament\Pages\Page;

class SystemAuditLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'Audit Logs';

    protected static string $view = 'filament.system.placeholder';

    public function getSubheading(): ?string
    {
        return 'Placeholder page for system audit logs.';
    }
}
