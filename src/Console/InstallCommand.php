<?php

namespace LaurentMeuwly\Docstore\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'docstore:install
                            {--m|migrations : Publish migrations}
                            {--f|force : Overwrite existing files}';

    protected $description = 'Install the Docstore package';

    public function handle(): int
    {
        $this->info('Installing Docstore...');

        $this->info('Publishing configuration file...');
        $this->callSilentOrVerbose('vendor:publish', [
            '--tag' => 'docstore-config',
            '--force' => $this->option('force'),
        ]);

        if ($this->option('migrations')) {
            $this->info('Publishing migrations...');
            $this->callSilentOrVerbose('vendor:publish', [
                '--tag' => 'docstore-migrations',
                '--force' => $this->option('force'),
            ]);
        }

        $this->info('Docstore installed successfully.');

        return self::SUCCESS;
    }

    /**
     * Call an Artisan command silently by default, or verbosely when requested.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function callSilentOrVerbose(string $command, array $arguments = []): void
    {
        if ($this->output->isVerbose()) {
            $this->call($command, $arguments);

            return;
        }

        $this->callSilent($command, $arguments);
    }
}
