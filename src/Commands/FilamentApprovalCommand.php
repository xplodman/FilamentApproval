<?php

namespace Xplodman\FilamentApproval\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FilamentApprovalCommand extends Command
{
    public $signature = 'filamentapproval:make-approval-page {resource} {--force}';

    public $description = 'Create a CreateRecord page with approval interception for a Filament resource';

    public function handle(): int
    {
        $resourceName = $this->argument('resource');
        $force = $this->option('force');

        // Convert to PascalCase if needed
        $resourceName = str_replace('Resource', '', $resourceName);
        $resourceName = ucfirst($resourceName);

        $resourceClass = $resourceName . 'Resource';
        $pageClass = 'Create' . $resourceName;

        // Determine the namespace based on the resource
        $namespace = 'App\\Filament\\Resources\\' . $resourceClass . '\\Pages';

        $stub = File::get(__DIR__ . '/../../stubs/CreateRecordWithApproval.php');
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $resourceClass, $stub);

        $path = app_path('Filament/Resources/' . $resourceClass . '/Pages/' . $pageClass . '.php');

        if (File::exists($path) && !$force) {
            $this->error('The page already exists. Use --force to overwrite.');
            return self::FAILURE;
        }

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($path));

        File::put($path, $stub);

        $this->info("Created approval-enabled create page: {$pageClass}");
        $this->comment("Don't forget to update your resource to use the new page class!");

        return self::SUCCESS;
    }
}
