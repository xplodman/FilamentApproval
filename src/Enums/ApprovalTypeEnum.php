<?php

namespace Xplodman\FilamentApproval\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ApprovalTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case CREATE = 'create';
    case EDIT = 'edit';
    case DELETE = 'delete';

    public function getLabel(): string
    {
        return match ($this) {
            self::CREATE => 'Create',
            self::EDIT => 'Edit',
            self::DELETE => 'Delete',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CREATE => 'success',
            self::EDIT => 'warning',
            self::DELETE => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CREATE => 'heroicon-o-plus-circle',
            self::EDIT => 'heroicon-o-pencil-square',
            self::DELETE => 'heroicon-o-trash',
        };
    }
}
