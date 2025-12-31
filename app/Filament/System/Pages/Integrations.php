<?php

namespace App\Filament\System\Pages;

use Filament\Pages\Page;

class Integrations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Integrations';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'Integrations';

    protected static string $view = 'filament.system.placeholder';

    public function getSubheading(): ?string
    {
        return 'Placeholder page for integrations configuration.';
    }
}
