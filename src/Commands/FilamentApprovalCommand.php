<?php

namespace Xplodman\FilamentApproval\Commands;

use Illuminate\Console\Command;

class FilamentApprovalCommand extends Command
{
    public $signature = 'filamentapproval';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
