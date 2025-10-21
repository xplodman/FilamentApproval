<?php

namespace Xplodman\FilamentApproval\Resources\ApprovalRequestResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource;

class ListApprovalRequests extends ListRecords
{
    protected static string $resource = ApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
