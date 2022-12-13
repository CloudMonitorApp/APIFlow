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
        if (! is_dir(app_path('Http/API'))) {
            mkdir(app_path('Http/API'));
        }

        $stub = str_replace(
            '{NAME}',
            $this->argument('name'),
            file_get_contents(dirname(__DIR__) .'/stubs/apiendpoint.stub')
        );

        file_put_contents(app_path('Http/API/'. $this->argument('name') .'.php'), $stub);

        return Command::SUCCESS;
    }
}
