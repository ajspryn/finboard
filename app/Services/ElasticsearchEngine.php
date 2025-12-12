<?php

namespace App\Services;

use Laravel\Scout\Engines\Engine;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Collection;

class ElasticsearchEngine extends Engine
{
    protected Client $client;
    protected string $index;

    public function __construct(Client $client, string $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    public function update($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $params = ['body' => []];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->index,
                    '_id' => $model->getScoutKey(),
                ]
            ];

            $params['body'][] = $model->toSearchableArray();
        });

        $this->client->bulk($params);
    }

    public function delete($models): void
    {
        if ($models->isEmpty()) {
            return;
        }

        $params = ['body' => []];

        $models->each(function ($model) use (&$params) {
            $params['body'][] = [
                'delete' => [
                    '_index' => $this->index,
                    '_id' => $model->getScoutKey(),
                ]
            ];
        });

        $this->client->bulk($params);
    }

    public function search(string $query, array $options = []): mixed
    {
        $searchParams = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['*'],
                        'fuzziness' => 'AUTO',
                        'minimum_should_match' => '70%',
                    ]
                ],
                'size' => $options['limit'] ?? 10000,
                'from' => $options['offset'] ?? 0,
            ]
        ];

        // Add filters if provided
        if (isset($options['filters'])) {
            $searchParams['body']['query'] = [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query' => $query,
                                'fields' => ['*'],
                                'fuzziness' => 'AUTO',
                                'minimum_should_match' => '70%',
                            ]
                        ]
                    ],
                    'filter' => $this->buildFilters($options['filters'])
                ]
            ];
        }

        return $this->client->search($searchParams);
    }

    public function paginate(string $query, int $perPage, int $page, array $options = []): mixed
    {
        $searchParams = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $query,
                        'fields' => ['*'],
                        'fuzziness' => 'AUTO',
                        'minimum_should_match' => '70%',
                    ]
                ],
                'size' => $perPage,
                'from' => ($page - 1) * $perPage,
            ]
        ];

        return $this->client->search($searchParams);
    }

    public function mapIds($results): array
    {
        return collect($results['hits']['hits'])->pluck('_id')->values()->all();
    }

    public function map(Collection $collection, $searchResults, $model): Collection
    {
        if (count($searchResults['hits']['hits']) === 0) {
            return $collection;
        }

        $ids = $this->mapIds($searchResults);
        $idsOrder = array_flip($ids);

        $models = $model->whereIn(
            $model->getScoutKeyName(),
            $ids
        )->get()->sortBy(function ($model) use ($idsOrder) {
            return $idsOrder[$model->getScoutKey()];
        })->values();

        return $collection->merge($models);
    }

    public function getTotalCount($results): int
    {
        return $results['hits']['total']['value'] ?? 0;
    }

    public function flush($model): void
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => [
                    'match' => [
                        '__class_name' => get_class($model)
                    ]
                ]
            ]
        ];

        $this->client->deleteByQuery($params);
    }

    public function createIndex(string $name, array $options = []): bool
    {
        $params = [
            'index' => $name,
        ];

        if (!empty($options)) {
            $params['body'] = $options;
        }

        try {
            $this->client->indices()->create($params);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteIndex(string $name): bool
    {
        try {
            $this->client->indices()->delete(['index' => $name]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function buildFilters(array $filters): array
    {
        $filterQueries = [];

        foreach ($filters as $field => $value) {
            $filterQueries[] = [
                'term' => [$field => $value]
            ];
        }

        return $filterQueries;
    }
}
