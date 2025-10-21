<?php

namespace Xplodman\FilamentApproval;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource;

class FilamentApprovalPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filamentapproval';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ApprovalRequestResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
