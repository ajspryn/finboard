<?php

namespace App\Console\Commands;

use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class ElasticsearchSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:setup {--reindex : Reindex all data after creating indices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Elasticsearch indices with proper mappings';

    protected ElasticsearchService $elasticsearch;

    protected array $indices = [
        'pembiayaans' => 'Pembiayaan (Financing) data',
        'tabungans' => 'Tabungan (Savings) data',
        'depositos' => 'Deposito (Deposit) data',
        'financial_highlights' => 'Financial Highlights data',
    ];

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
        $this->info('🚀 Starting Elasticsearch setup...');

        // Test connection
        if (!$this->testConnection()) {
            return Command::FAILURE;
        }

        // Create indices
        $this->createIndices();

        // Reindex data if requested
        if ($this->option('reindex')) {
            $this->call('elasticsearch:reindex');
        }

        $this->info('✅ Elasticsearch setup completed successfully!');
        $this->showUsageInstructions();

        return Command::SUCCESS;
    }

    protected function testConnection(): bool
    {
        $this->info('🔍 Testing Elasticsearch connection...');

        try {
            $info = $this->elasticsearch->getClient()->info();
            $this->info("✅ Connected to Elasticsearch v{$info['version']['number']}");
            return true;
        } catch (\Exception $e) {
            $this->error("❌ Failed to connect to Elasticsearch: {$e->getMessage()}");
            $this->warn('Please make sure Elasticsearch is running and check your configuration in config/elasticsearch.php');
            return false;
        }
    }

    protected function createIndices(): void
    {
        $this->info('📊 Creating Elasticsearch indices...');

        foreach ($this->indices as $indexName => $description) {
            $this->info("  Creating index: {$indexName} ({$description})");

            if ($this->elasticsearch->indexExists($indexName)) {
                $this->warn("    Index '{$indexName}' already exists, skipping...");
                continue;
            }

            $mappings = config('elasticsearch.mappings.' . $indexName, []);

            if ($this->elasticsearch->createIndex($indexName, $mappings)) {
                $this->info("    ✅ Created index: {$indexName}");
            } else {
                $this->error("    ❌ Failed to create index: {$indexName}");
            }
        }
    }

    protected function showUsageInstructions(): void
    {
        $this->info("\n📖 Usage Instructions:");
        $this->line('1. To reindex all data:');
        $this->comment('   php artisan elasticsearch:reindex');
        $this->line('2. To reindex specific model:');
        $this->comment('   php artisan elasticsearch:reindex pembiayaan');
        $this->line('3. API Endpoints:');
        $this->comment('   GET /api/search/pembiayaan?q=search_term');
        $this->comment('   GET /api/search/tabungan?q=search_term');
        $this->comment('   GET /api/search/deposito?q=search_term');
        $this->comment('   GET /api/search/financial-highlights?q=search_term');
        $this->comment('   GET /api/search/all?q=search_term');
        $this->line('4. Available commands:');
        $this->comment('   php artisan elasticsearch:create-index <index>');
        $this->comment('   php artisan elasticsearch:reindex [model]');
    }
}
