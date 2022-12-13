<?php

namespace CloudMonitor\APIFlow\Commands;

use Illuminate\Console\Command;

class APIEndpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create API endpoint controller class';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->checkFolder();

        if ($this->isDuplicate()) {
            return Command::FAILURE;
        }

        $this->create();

        return Command::SUCCESS;
    }

    /**
     * If API folder does't exist, create it.
     * 
     * @return void
     */
    private function checkFolder(): void
    {
        if (! is_dir(app_path('Http/API'))) {
            mkdir(app_path('Http/API'));
        }
    }

    /**
     * Check if API Controller is already existing.
     * 
     * @return bool
     */
    private function isDuplicate(): bool
    {
        if (is_file(app_path('Http/API/'. $this->argument('name') .'.php'))) {
            $this->components->error('API Controller ['. app_path('Http/API/'. $this->argument('name') .'.php') .'] already exists.');
            return true;
        }

        return false;
    }

    /**
     * Create API Controller.
     * 
     * @return void
     */
    private function create(): void
    {
        $stub = str_replace(
            '{NAME}',
            $this->argument('name'),
            file_get_contents(dirname(__DIR__) .'/stubs/apiendpoint.stub')
        );

        file_put_contents(app_path('Http/API/'. $this->argument('name') .'.php'), $stub);

        $this->components->info('API Controller ['. app_path('Http/API/'. $this->argument('name') .'.php') .'] created successfully.');
    }
}
