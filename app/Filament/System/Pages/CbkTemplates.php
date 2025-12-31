<?php

namespace App\Filament\System\Pages;

use Filament\Pages\Page;

class CbkTemplates extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'CBK Templates';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'CBK Templates';

    protected static string $view = 'filament.system.placeholder';

    public function getSubheading(): ?string
    {
        return 'Placeholder page for CBK templates management.';
    }
}
