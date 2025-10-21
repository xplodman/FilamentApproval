<?php

namespace Xplodman\FilamentApproval;

use Filament\Panel;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Xplodman\FilamentApproval\Resources\ApprovalRequestResource;
use Xplodman\FilamentApproval\Testing\TestsFilamentApproval;

class FilamentApprovalServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filamentapproval';

    public static string $viewNamespace = 'filamentapproval';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('xplodman/filamentapproval');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Register the configurable model
        $this->app->bind(
            \Xplodman\FilamentApproval\Models\ApprovalRequest::class,
            config('filamentapproval.approval_request_model', \Xplodman\FilamentApproval\Models\ApprovalRequest::class)
        );

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filamentapproval/{$file->getFilename()}"),
                ], 'filamentapproval-stubs');
            }

            // Directly publish the generic model into app/Models via a separate tag
            $this->publishes([
                __DIR__ . '/../stubs/ApprovalRequestModel.php' => app_path('Models/ApprovalRequest.php'),
            ], 'filamentapproval-model');

            // Directly publish the policy into app/Policies via a separate tag
            $this->publishes([
                __DIR__ . '/../stubs/ApprovalRequestPolicy.php' => app_path('Policies/ApprovalRequestPolicy.php'),
            ], 'filamentapproval-policy');
        }

        // Testing
        Testable::mixin(new TestsFilamentApproval);
    }

    public function packageRegistered(): void
    {
        // Register Filament resources if auto-registration is enabled
        if (config('filamentapproval.auto_register_resource', false)) {
            Panel::configureUsing(function (Panel $panel) {
                $panel->resources([
                    ApprovalRequestResource::class,
                ]);
            });
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'xplodman/filamentapproval';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // No assets for now
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_approval_requests_table',
        ];
    }
}
