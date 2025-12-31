<?php

namespace App\Filament\System\Pages;

use Filament\Pages\Page;

class SystemReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.system.placeholder';

    public function getSubheading(): ?string
    {
        return 'Placeholder page for system-level reporting.';
    }
}
