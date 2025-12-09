<?php

namespace MohamedAyman\LaravelPostmanGenerator\Commands;

use Illuminate\Console\Command;
use MohamedAyman\LaravelPostmanGenerator\PostmanGenerator;

class GeneratePostmanCollectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postman:generate 
                            {--output= : Output file path}
                            {--name= : Collection name}
                            {--base-url= : Base URL for the API}
                            {--include=* : Routes to include (web, api, all)}
                            {--exclude=* : Route patterns to exclude}
                            {--update-api : Update existing Postman collection via API}
                            {--collection-id= : Postman collection ID for API update}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Postman collection from Laravel routes, controllers, and validation rules';

    /**
     * Execute the console command.
     */
    public function handle(PostmanGenerator $generator): int
    {
        $this->info('🚀 Starting Postman collection generation...');

        $options = [
            'output_path' => $this->option('output') ?? config('postman-generator.output_path'),
            'collection_name' => $this->option('name') ?? config('postman-generator.collection_name'),
            'base_url' => $this->option('base-url') ?? config('postman-generator.base_url'),
            'include_routes' => $this->option('include') ?: config('postman-generator.include_routes', ['api']),
            'exclude_routes' => $this->option('exclude') ?: config('postman-generator.exclude_routes', []),
        ];

        $this->info('📋 Scanning routes...');
        $collection = $generator->generate($options);

        $this->info('💾 Saving collection to file...');
        $outputPath = $options['output_path'];
        
        if ($generator->saveToFile($collection, $outputPath)) {
            $this->info("✅ Collection saved successfully to: {$outputPath}");
        } else {
            $this->error("❌ Failed to save collection to: {$outputPath}");
            return Command::FAILURE;
        }

        // Update via API if requested
        if ($this->option('update-api')) {
            $this->info('🔄 Updating Postman collection via API...');
            
            $apiOptions = [
                'collection_id' => $this->option('collection-id') ?? config('postman-generator.postman.collection_id'),
            ];
            
            if ($generator->updateViaApi($collection, $apiOptions)) {
                $this->info('✅ Collection updated successfully via API');
            } else {
                $this->warn('⚠️  Failed to update collection via API. Check your API key and collection ID.');
            }
        }

        $this->info('✨ Done!');
        
        return Command::SUCCESS;
    }
}

