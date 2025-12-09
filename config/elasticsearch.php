<?php

return [
     /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for Elasticsearch.
    | This configuration will be used by the Elasticsearch client.
    |
    */

     'hosts' => [
          [
               'host' => env('ELASTICSEARCH_HOST', 'my-elasticsearch-project-fd9e80.es.us-central1.gcp.elastic.cloud'),
               'port' => env('ELASTICSEARCH_PORT', 443),
               'scheme' => env('ELASTICSEARCH_SCHEME', 'https'),
               'user' => env('ELASTICSEARCH_USER', null),
               'pass' => env('ELASTICSEARCH_PASS', null),
          ],
     ],

     'api_key' => env('ELASTICSEARCH_API_KEY', null),

     /*
    |--------------------------------------------------------------------------
    | Default Index Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the default index settings for Elasticsearch.
    |
    */

     'index' => [
          'prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'finboard_'),
          // Note: Shards and replicas are not configurable in serverless mode
          // 'settings' => [
          //      'number_of_shards' => env('ELASTICSEARCH_SHARDS', 1),
          //      'number_of_replicas' => env('ELASTICSEARCH_REPLICAS', 0),
          // ],
     ],

     /*
    |--------------------------------------------------------------------------
    | Index Mappings
    |--------------------------------------------------------------------------
    |
    | Define the mappings for different indices
    |
    */

     'mappings' => [
          'pembiayaans' => [
               'properties' => [
                    'nokontrak' => ['type' => 'keyword'],
                    'nama' => ['type' => 'text', 'analyzer' => 'standard'],
                    'nmao' => ['type' => 'keyword'],
                    'alamat' => ['type' => 'text'],
                    'telprmh' => ['type' => 'keyword'],
                    'hp' => ['type' => 'keyword'],
                    'fnama' => ['type' => 'text'],
                    'kdprd' => ['type' => 'keyword'],
                    'kdloc' => ['type' => 'keyword'],
                    'kelurahan' => ['type' => 'keyword'],
                    'kecamatan' => ['type' => 'keyword'],
                    'kota' => ['type' => 'keyword'],
                    'period_year' => ['type' => 'integer'],
                    'period_month' => ['type' => 'integer'],
                    'tgleff' => ['type' => 'date'],
                    'tglexp' => ['type' => 'date'],
                    'mdlawal' => ['type' => 'double'],
                    'mgnawal' => ['type' => 'double'],
                    'osmdlc' => ['type' => 'double'],
                    'osmgnc' => ['type' => 'double'],
                    'angsmdl' => ['type' => 'double'],
                    'angsmgn' => ['type' => 'double'],
                    'sahirrp' => ['type' => 'double'],
                    'colbaru' => ['type' => 'keyword'],
               ],
          ],

          'tabungans' => [
               'properties' => [
                    'notab' => ['type' => 'keyword'],
                    'nocif' => ['type' => 'keyword'],
                    'fnama' => ['type' => 'text', 'analyzer' => 'standard'],
                    'namaqq' => ['type' => 'text'],
                    'sahirrp' => ['type' => 'double'],
                    'saldoblok' => ['type' => 'double'],
                    'tax' => ['type' => 'double'],
                    'avgeom' => ['type' => 'double'],
                    'stsrec' => ['type' => 'keyword'],
                    'stsrest' => ['type' => 'keyword'],
                    'stspep' => ['type' => 'keyword'],
                    'kdrisk' => ['type' => 'keyword'],
                    'kodeloc' => ['type' => 'keyword'],
                    'period_year' => ['type' => 'integer'],
                    'period_month' => ['type' => 'integer'],
                    'tgltrnakh' => ['type' => 'date'],
                    'tgllhr' => ['type' => 'date'],
               ],
          ],

          'depositos' => [
               'properties' => [
                    'nodep' => ['type' => 'keyword'],
                    'nocif' => ['type' => 'keyword'],
                    'nobilyet' => ['type' => 'keyword'],
                    'nama' => ['type' => 'text', 'analyzer' => 'standard'],
                    'nomrp' => ['type' => 'double'],
                    'tax' => ['type' => 'double'],
                    'bnghtg' => ['type' => 'double'],
                    'nisbahrp' => ['type' => 'double'],
                    'kdprd' => ['type' => 'keyword'],
                    'kodeaoh' => ['type' => 'keyword'],
                    'stsrec' => ['type' => 'keyword'],
                    'period_year' => ['type' => 'integer'],
                    'period_month' => ['type' => 'integer'],
                    'tglbuka' => ['type' => 'date'],
                    'tgleff' => ['type' => 'date'],
                    'tgljtempo' => ['type' => 'date'],
                    'tgllhr' => ['type' => 'date'],
               ],
          ],

          'financial_highlights' => [
               'properties' => [
                    'period_year' => ['type' => 'integer'],
                    'period_month' => ['type' => 'integer'],
                    'car' => ['type' => 'double'],
                    'roa' => ['type' => 'double'],
                    'roe' => ['type' => 'double'],
                    'aset' => ['type' => 'double'],
                    'pembiayaan' => ['type' => 'double'],
                    'laba_rugi' => ['type' => 'double'],
                    'biaya' => ['type' => 'double'],
                    'pendapatan' => ['type' => 'double'],
                    'dpk' => ['type' => 'double'],
                    'fdr' => ['type' => 'double'],
                    'npf' => ['type' => 'double'],
                    'bopo' => ['type' => 'double'],
                    'cash_ratio' => ['type' => 'double'],
               ],
          ],
     ],
];
