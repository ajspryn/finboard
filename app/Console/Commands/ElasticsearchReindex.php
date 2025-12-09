<?php

namespace App\Console\Commands;

use App\Models\Pembiayaan;
use App\Models\Tabungan;
use App\Models\Deposito;
use App\Models\FinancialHighlight;
use App\Services\ElasticsearchService;
use Illuminate\Console\Command;

class ElasticsearchReindex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:reindex {model? : Specific model to reindex (pembiayaan, tabungan, deposito, financial_highlight)} {--create-index : Create index if it doesn\'t exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex all data from database to Elasticsearch';

    protected ElasticsearchService $elasticsearch;

    protected array $models = [
        'pembiayaan' => Pembiayaan::class,
        'tabungan' => Tabungan::class,
        'deposito' => Deposito::class,
        'financial_highlight' => FinancialHighlight::class,
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
        $specificModel = $this->argument('model');
        $createIndex = $this->option('create-index');

        if ($specificModel) {
            if (!isset($this->models[$specificModel])) {
                $this->error("Invalid model: {$specificModel}. Available models: " . implode(', ', array_keys($this->models)));
                return Command::FAILURE;
            }

            return $this->reindexModel($specificModel, $createIndex);
        }

        // Reindex all models
        $this->info('🔄 Starting reindex of all models...');

        $totalResults = [];
        foreach (array_keys($this->models) as $modelName) {
            $result = $this->reindexModel($modelName, $createIndex);
            if ($result === Command::FAILURE) {
                return Command::FAILURE;
            }
            $totalResults[$modelName] = $result;
        }

        $this->displaySummary($totalResults);
        return Command::SUCCESS;
    }

    protected function reindexModel(string $modelName, bool $createIndex): int
    {
        $modelClass = $this->models[$modelName];
        $indexName = strtolower($modelName) . 's';

        $this->info("📊 Reindexing {$modelName}...");

        // Create index if requested
        if ($createIndex && !$this->elasticsearch->indexExists($indexName)) {
            $this->info("  Creating index: {$indexName}");
            $mappings = config('elasticsearch.mappings.' . $indexName, []);
            if (!$this->elasticsearch->createIndex($indexName, $mappings)) {
                $this->error("  ❌ Failed to create index: {$indexName}");
                return Command::FAILURE;
            }
        }

        // Check if index exists
        if (!$this->elasticsearch->indexExists($indexName)) {
            $this->error("  ❌ Index '{$indexName}' does not exist. Use --create-index to create it.");
            return Command::FAILURE;
        }

        // Get total count
        $totalRecords = $modelClass::count();
        $this->info("  Found {$totalRecords} records to index");

        if ($totalRecords === 0) {
            $this->warn("  ⚠️  No records found for {$modelName}");
            return Command::SUCCESS;
        }

        // Reindex using the model's reindexAll method
        $result = $modelClass::reindexAll();

        $this->info("  ✅ Completed: {$result['indexed']} indexed, {$result['errors']} errors");

        return Command::SUCCESS;
    }

    protected function displaySummary(array $results): void
    {
        $this->info("\n📈 Reindex Summary:");
        $this->table(
            ['Model', 'Records', 'Indexed', 'Errors'],
            array_map(function ($model, $result) {
                return [
                    $model,
                    $result['total_records'] ?? 0,
                    $result['indexed'] ?? 0,
                    $result['errors'] ?? 0,
                ];
            }, array_keys($results), $results)
        );
    }
}
