<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class ElasticsearchCreateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:create-index {index : The index name to create} {--force : Force recreate if index exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an Elasticsearch index with proper mapping';

    protected ElasticsearchService $elasticsearch;

    public function __construct(ElasticsearchService $elasticsearch)
    {
        parent::__construct();
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $indexName = $this->argument('index');
        $force = $this->option('force');

        $this->info("Creating Elasticsearch index: {$indexName}");

        // Check if index exists
        if ($this->elasticsearch->indexExists($indexName)) {
            if (!$force) {
                $this->error("Index '{$indexName}' already exists. Use --force to recreate.");
                return Command::FAILURE;
            }

            $this->warn("Index '{$indexName}' exists. Deleting and recreating...");
            if (!$this->elasticsearch->deleteIndex($indexName)) {
                $this->error("Failed to delete existing index '{$indexName}'");
                return Command::FAILURE;
            }
        }

        // Get mapping from config
        $mappings = config('elasticsearch.mappings.' . $indexName, []);

        if (empty($mappings)) {
            $this->warn("No mapping found for index '{$indexName}' in config. Creating without mapping.");
        }

        // Create the index
        if ($this->elasticsearch->createIndex($indexName, $mappings)) {
            $this->info("✅ Successfully created Elasticsearch index: {$indexName}");
            return Command::SUCCESS;
        } else {
            $this->error("❌ Failed to create Elasticsearch index: {$indexName}");
            return Command::FAILURE;
        }
    }
}
