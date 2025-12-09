<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;

class ElasticsearchService
{
     protected Client $client;
     protected string $indexPrefix;

     public function __construct()
     {
          $hosts = config('elasticsearch.hosts', []);
          $formattedHosts = [];

          foreach ($hosts as $host) {
               $formattedHost = '';
               if (isset($host['scheme'])) {
                    $formattedHost .= $host['scheme'] . '://';
               } else {
                    $formattedHost .= 'http://';
               }

               if (isset($host['user']) && isset($host['pass'])) {
                    $formattedHost .= $host['user'] . ':' . $host['pass'] . '@';
               }

               $formattedHost .= $host['host'];

               if (isset($host['port'])) {
                    $formattedHost .= ':' . $host['port'];
               }

               $formattedHosts[] = $formattedHost;
          }

          $builder = ClientBuilder::create()
               ->setHosts($formattedHosts);

          $apiKey = config('elasticsearch.api_key');
          if ($apiKey) {
               $builder->setApiKey($apiKey);
          }

          $this->client = $builder->build();

          $this->indexPrefix = config('elasticsearch.index.prefix');
     }

     /**
      * Get the Elasticsearch client instance
      */
     public function getClient(): Client
     {
          return $this->client;
     }

     /**
      * Get the full index name with prefix
      */
     public function getIndexName(string $index): string
     {
          return $this->indexPrefix . $index;
     }

     /**
      * Create an index with mapping
      */
     public function createIndex(string $indexName, array $mapping = []): bool
     {
          try {
               $params = [
                    'index' => $this->getIndexName($indexName),
               ];

               $body = [];

               // Only add settings if not running in serverless mode
               $settings = config('elasticsearch.index.settings');
               if (!empty($settings)) {
                    $body['settings'] = $settings;
               }

               if (!empty($mapping)) {
                    $body['mappings'] = $mapping;
               }

               if (!empty($body)) {
                    $params['body'] = $body;
               }

               $response = $this->client->indices()->create($params);
               Log::info("Elasticsearch index created: {$indexName}");

               return true;
          } catch (\Exception $e) {
               Log::error("Failed to create Elasticsearch index: {$indexName}", [
                    'error' => $e->getMessage(),
               ]);
               return false;
          }
     }

     /**
      * Delete an index
      */
     public function deleteIndex(string $indexName): bool
     {
          try {
               $response = $this->client->indices()->delete([
                    'index' => $this->getIndexName($indexName),
               ]);
               Log::info("Elasticsearch index deleted: {$indexName}");

               return true;
          } catch (\Exception $e) {
               Log::error("Failed to delete Elasticsearch index: {$indexName}", [
                    'error' => $e->getMessage(),
               ]);
               return false;
          }
     }

     /**
      * Check if index exists
      */
     public function indexExists(string $indexName): bool
     {
          try {
               $response = $this->client->indices()->exists([
                    'index' => $this->getIndexName($indexName),
               ]);
               return $response->getStatusCode() === 200;
          } catch (\Exception $e) {
               return false;
          }
     }

     /**
      * Index a document
      */
     public function indexDocument(string $indexName, string $id, array $data): bool
     {
          try {
               $response = $this->client->index([
                    'index' => $this->getIndexName($indexName),
                    'id' => $id,
                    'body' => $data,
               ]);
               Log::debug("Document indexed: {$indexName}:{$id}");

               return true;
          } catch (\Exception $e) {
               Log::error("Failed to index document: {$indexName}:{$id}", [
                    'error' => $e->getMessage(),
                    'data' => $data,
               ]);
               return false;
          }
     }

     /**
      * Update a document
      */
     public function updateDocument(string $indexName, string $id, array $data): bool
     {
          try {
               $response = $this->client->update([
                    'index' => $this->getIndexName($indexName),
                    'id' => $id,
                    'body' => [
                         'doc' => $data,
                    ],
               ]);
               Log::debug("Document updated: {$indexName}:{$id}");

               return true;
          } catch (\Exception $e) {
               Log::error("Failed to update document: {$indexName}:{$id}", [
                    'error' => $e->getMessage(),
                    'data' => $data,
               ]);
               return false;
          }
     }

     /**
      * Delete a document
      */
     public function deleteDocument(string $indexName, string $id): bool
     {
          try {
               $response = $this->client->delete([
                    'index' => $this->getIndexName($indexName),
                    'id' => $id,
               ]);
               Log::debug("Document deleted: {$indexName}:{$id}");

               return true;
          } catch (\Exception $e) {
               Log::error("Failed to delete document: {$indexName}:{$id}", [
                    'error' => $e->getMessage(),
               ]);
               return false;
          }
     }

     /**
      * Search documents
      */
     public function search(string $indexName, array $query, int $from = 0, int $size = 10): array
     {
          try {
               $params = [
                    'index' => $this->getIndexName($indexName),
                    'body' => [
                         'query' => $query,
                         'from' => $from,
                         'size' => $size,
                    ],
               ];

               $response = $this->client->search($params);

               return [
                    'success' => true,
                    'total' => $response['hits']['total']['value'] ?? 0,
                    'hits' => array_map(function ($hit) {
                         return [
                              'id' => $hit['_id'],
                              'score' => $hit['_score'],
                              'source' => $hit['_source'],
                         ];
                    }, $response['hits']['hits']),
               ];
          } catch (\Exception $e) {
               Log::error("Search failed on index: {$indexName}", [
                    'error' => $e->getMessage(),
                    'query' => $query,
               ]);

               return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'total' => 0,
                    'hits' => [],
               ];
          }
     }

     /**
      * Bulk index documents
      */
     public function bulkIndex(string $indexName, array $documents): array
     {
          try {
               $params = ['body' => []];

               foreach ($documents as $id => $document) {
                    $params['body'][] = [
                         'index' => [
                              '_index' => $this->getIndexName($indexName),
                              '_id' => $id,
                         ],
                    ];
                    $params['body'][] = $document;
               }

               $response = $this->client->bulk($params);

               $successful = $response['items'] ?? [];
               $errors = array_filter($successful, function ($item) {
                    return isset($item['index']['error']);
               });

               Log::info("Bulk index completed for {$indexName}", [
                    'total' => count($documents),
                    'successful' => count($successful) - count($errors),
                    'errors' => count($errors),
               ]);

               return [
                    'success' => count($errors) === 0,
                    'total' => count($documents),
                    'successful' => count($successful) - count($errors),
                    'errors' => count($errors),
                    'items' => $successful,
               ];
          } catch (\Exception $e) {
               Log::error("Bulk index failed for {$indexName}", [
                    'error' => $e->getMessage(),
                    'document_count' => count($documents),
               ]);

               return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'total' => count($documents),
                    'successful' => 0,
                    'errors' => count($documents),
                    'items' => [],
               ];
          }
     }

     /**
      * Get index statistics
      */
     public function getIndexStats(string $indexName): array
     {
          try {
               $response = $this->client->indices()->stats([
                    'index' => $this->getIndexName($indexName),
               ]);

               $stats = $response['indices'][$this->getIndexName($indexName)] ?? [];

               return [
                    'success' => true,
                    'docs_count' => $stats['total']['docs']['count'] ?? 0,
                    'docs_deleted' => $stats['total']['docs']['deleted'] ?? 0,
                    'store_size' => $stats['total']['store']['size_in_bytes'] ?? 0,
                    'index_size' => $stats['primaries']['store']['size_in_bytes'] ?? 0,
               ];
          } catch (\Exception $e) {
               Log::error("Failed to get index stats: {$indexName}", [
                    'error' => $e->getMessage(),
               ]);

               return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'docs_count' => 0,
                    'docs_deleted' => 0,
                    'store_size' => 0,
                    'index_size' => 0,
               ];
          }
     }
}
