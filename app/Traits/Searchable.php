<?php

namespace App\Traits;

use App\Services\ElasticsearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

trait Searchable
{
     /**
      * Boot the Searchable trait for a model.
      */
     public static function bootSearchable()
     {
          static::created(function (Model $model) {
               $model->elasticsearchIndex();
          });

          static::updated(function (Model $model) {
               $model->elasticsearchUpdate();
          });

          static::deleted(function (Model $model) {
               $model->elasticsearchDelete();
          });
     }

     /**
      * Get the Elasticsearch service instance
      */
     protected function getElasticsearchService(): ElasticsearchService
     {
          return App::make(ElasticsearchService::class);
     }

     /**
      * Get the index name for this model
      */
     public function getSearchIndex(): string
     {
          return $this->searchIndex ?? strtolower(class_basename($this)) . 's';
     }

     /**
      * Get the searchable data array for the model
      */
     public function toSearchableArray(): array
     {
          return $this->toArray();
     }

     /**
      * Index the model in Elasticsearch
      */
     public function elasticsearchIndex(): bool
     {
          return $this->getElasticsearchService()->indexDocument(
               $this->getSearchIndex(),
               $this->getKey(),
               $this->toSearchableArray()
          );
     }

     /**
      * Update the model in Elasticsearch
      */
     public function elasticsearchUpdate(): bool
     {
          return $this->getElasticsearchService()->updateDocument(
               $this->getSearchIndex(),
               $this->getKey(),
               $this->toSearchableArray()
          );
     }

     /**
      * Delete the model from Elasticsearch
      */
     public function elasticsearchDelete(): bool
     {
          return $this->getElasticsearchService()->deleteDocument(
               $this->getSearchIndex(),
               $this->getKey()
          );
     }

     /**
      * Search the model using Elasticsearch
      */
     public static function search(string $query, array $options = []): array
     {
          $instance = new static();
          $elasticsearch = $instance->getElasticsearchService();

          $searchQuery = [
               'multi_match' => [
                    'query' => $query,
                    'fields' => $instance->getSearchFields(),
                    'fuzziness' => 'AUTO',
               ],
          ];

          // Add filters if provided
          if (!empty($options['filters'])) {
               $searchQuery = [
                    'bool' => [
                         'must' => [$searchQuery],
                         'filter' => $options['filters'],
                    ],
               ];
          }

          $from = $options['from'] ?? 0;
          $size = $options['size'] ?? 10;

          $results = $elasticsearch->search(
               $instance->getSearchIndex(),
               $searchQuery,
               $from,
               $size
          );

          if (!$results['success']) {
               return [
                    'total' => 0,
                    'hits' => [],
                    'error' => $results['error'],
               ];
          }

          // Convert Elasticsearch results to model instances
          $models = [];
          foreach ($results['hits'] as $hit) {
               $model = static::find($hit['id']);
               if ($model) {
                    $model->_score = $hit['score'];
                    $models[] = $model;
               }
          }

          return [
               'total' => $results['total'],
               'hits' => $models,
          ];
     }

     /**
      * Get the fields that should be searched
      */
     protected function getSearchFields(): array
     {
          return ['*'];
     }

     /**
      * Reindex all records of this model
      */
     public static function reindexAll(): array
     {
          $instance = new static();
          $elasticsearch = $instance->getElasticsearchService();

          $query = static::query();
          $totalRecords = $query->count();

          $batchSize = 1000;
          $totalIndexed = 0;
          $totalErrors = 0;

          $query->chunk($batchSize, function ($records) use ($elasticsearch, $instance, &$totalIndexed, &$totalErrors) {
               $documents = [];
               foreach ($records as $record) {
                    $documents[$record->getKey()] = $record->toSearchableArray();
               }

               $result = $elasticsearch->bulkIndex($instance->getSearchIndex(), $documents);
               $totalIndexed += $result['successful'];
               $totalErrors += $result['errors'];
          });

          return [
               'total_records' => $totalRecords,
               'indexed' => $totalIndexed,
               'errors' => $totalErrors,
          ];
     }
}
