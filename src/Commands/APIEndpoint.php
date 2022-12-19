<?php

namespace CloudMonitor\APIFlow\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class APIEndpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:api {name}
        {--B|bind=     : Bind API controller to a model.}
        {--A|authorize : Attach controller to policy.}
        {--P|policy    : Create policy class.}
        {--M|model     : Create model class.}
        {--R|resource  : Create resource class.}
    ';

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
            ['{NAME}', '{MODEL}', '{model}'],
            [$this->argument('name'), $this->modelClass(), $this->modelParameter()],
            file_get_contents(dirname(__DIR__) .'/stubs/apiendpoint.stub')
        );

        if (! $this->option('authorize')) {
            $stub = str_ireplace(
                '
    /**
     * Create the controller instance.
     *
     * @param array $params
     * @return void
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->authorizeResource(parent::model(), parent::parameter());
    }
',
                '',
                $stub
            );
        }

        file_put_contents(app_path('Http/API/'. $this->argument('name') .'.php'), $stub);

        $this->components->info('API Controller ['. app_path('Http/API/'. $this->argument('name') .'.php') .'] created successfully.');

        if ($this->option('policy')) {
            Artisan::call('make:policy '. $this->modelClass() .'Policy --model='. $this->modelClass());
        }

        if ($this->option('resource')) {
            Artisan::call('make:resource '. $this->modelClass());
        }

        if ($this->option('model')) {
            Artisan::call('make:model '. $this->modelClass());
        }
    }

    /**
     * 
     */
    private function modelClass(): string
    {
        if (! $this->option('bind')) {
            return 'int';
        }

        return ucfirst($this->option('bind'));
    }

    /**
     * 
     */
    private function modelParameter(): string
    {
        if (! $this->option('bind')) {
            return 'id';
        }

        return strtolower($this->modelClass());
    }
}
