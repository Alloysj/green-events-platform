<?php

namespace App\Filament\System\Pages;

use Filament\Pages\Page;

class SystemUsers extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'System Users';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $title = 'System Users';

    protected static string $view = 'filament.system.placeholder';

    public function getSubheading(): ?string
    {
        return 'Placeholder page for system users and roles.';
    }
}
