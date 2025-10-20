<?php

namespace {{ namespace }}\Pages;

use {{ namespace }}\{{ class }};
use Filament\Resources\Pages\CreateRecord;
use Xplodman\FilamentApproval\Concerns\InterceptsCreateForApproval;

class Create{{ class }} extends CreateRecord
{
    use InterceptsCreateForApproval;
    
    protected static string $resource = {{ class }}::class;
}
