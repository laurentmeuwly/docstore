<?php

namespace LaurentMeuwly\Docstore\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'docstore:install
                            {--m|migrations : Publish migrations}
                            {--f|force : Overwrite existing files}';

    protected $description = 'Install the Docstore package';

    public function handle()
    {
        $this->info('Installing Docstore...');

        // Publish config
        $this->info('Publishing configuration file...');
        $this->callSilentOrVerbose('vendor:publish', [
            '--tag' => 'docstore-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations if required
        if ($this->option('migrations')) {
            $this->info('Publishing migrations...');
            $this->callSilentOrVerbose('vendor:publish', [
                '--tag' => 'docstore-migrations',
                '--force' => $this->option('force'),
            ]);
        }

        $this->info('Docstore installed successfully.');

        return Command::SUCCESS;
    }

    /**
     * Helper: use call() or callSilent() depending on verbose mode.
     */
    protected function callSilentOrVerbose(string $command, array $arguments = []): void
    {
        if ($this->output->isVerbose()) {
            $this->call($command, $arguments);
        } else {
            $this->callSilent($command, $arguments);
        }
    }
}
