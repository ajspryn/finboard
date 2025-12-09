# Elasticsearch Integration for FinBoard

This document describes the Elasticsearch integration implemented in the FinBoard Laravel application.

## Overview

Elasticsearch has been integrated to provide fast, full-text search capabilities across financial data including:

- Pembiayaan (Financing) records
- Tabungan (Savings) accounts
- Deposito (Deposit) accounts
- Financial Highlights data

## Features

### 🔍 Search Capabilities

- Full-text search across customer names, account numbers, and identifiers
- Period-based filtering (year/month)
- Relevance scoring
- Multi-index search

### 🔄 Real-time Indexing

- Automatic indexing on create/update/delete operations
- Bulk reindexing for existing data
- Configurable index mappings

### 📊 API Endpoints

- `GET /api/search/pembiayaan?q=search_term&period_year=2024&period_month=12`
- `GET /api/search/tabungan?q=search_term`
- `GET /api/search/deposito?q=search_term`
- `GET /api/search/financial-highlights?q=search_term`
- `GET /api/search/all?q=search_term&type=pembiayaan`

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Elasticsearch Configuration
ELASTICSEARCH_HOST=localhost
ELASTICSEARCH_PORT=9200
ELASTICSEARCH_SCHEME=http
ELASTICSEARCH_USER=
ELASTICSEARCH_PASS=
ELASTICSEARCH_INDEX_PREFIX=finboard_
```

### Index Mappings

Mappings are configured in `config/elasticsearch.php` with proper field types for optimal search performance.

## Setup Instructions

### 1. Install and Start Elasticsearch

```bash
# Using Docker
docker run -d -p 9200:9200 -p 9300:9300 -e "discovery.type=single-node" elasticsearch:8.11.0

# Or install locally
# Follow: https://www.elastic.co/guide/en/elasticsearch/reference/current/install-elasticsearch.html
```

### 2. Configure Laravel

```bash
# Copy environment variables
cp .env.example .env

# Edit .env with your Elasticsearch settings
```

### 3. Setup Elasticsearch Indices

```bash
# Create indices with proper mappings
php artisan elasticsearch:setup

# Reindex all existing data
php artisan elasticsearch:setup --reindex
```

### 4. Alternative Commands

```bash
# Create individual indices
php artisan elasticsearch:create-index pembiayaans
php artisan elasticsearch:create-index tabungans
php artisan elasticsearch:create-index depositos
php artisan elasticsearch:create-index financial_highlights

# Reindex specific models
php artisan elasticsearch:reindex pembiayaan
php artisan elasticsearch:reindex tabungan
php artisan elasticsearch:reindex deposito
php artisan elasticsearch:reindex financial_highlight

# Reindex all models
php artisan elasticsearch:reindex
```

## API Usage Examples

### Search Pembiayaan

```bash
curl "http://localhost:8000/api/search/pembiayaan?q=john&period_year=2024&limit=10"
```

Response:

```json
{
  "success": true,
  "query": "john",
  "total": 25,
  "results": [
    {
      "id": 123,
      "nokontrak": "PF001234",
      "nama": "John Doe",
      "nmao": "AO001",
      "alamat": "Jakarta",
      "sahirrp": 50000000.0,
      "colbaru": "1",
      "period_year": 2024,
      "period_month": 12,
      "score": 1.25
    }
  ]
}
```

### Universal Search

```bash
curl "http://localhost:8000/api/search/all?q=john&type=pembiayaan"
```

## Architecture

### Components

- **ElasticsearchService**: Core service for Elasticsearch operations
- **Searchable Trait**: Provides search functionality to models
- **SearchController**: API endpoints for search requests
- **Console Commands**: Management commands for setup and maintenance

### Index Structure

- `finboard_pembiayaans`: Financing data
- `finboard_tabungans`: Savings data
- `finboard_depositos`: Deposit data
- `finboard_financial_highlights`: Financial metrics

### Searchable Fields

- **Pembiayaan**: Contract number, customer name, AO name, address, CIF
- **Tabungan**: Account number, CIF, customer name, ID number, phone
- **Deposito**: Deposit number, CIF, customer name, ID number, phone
- **Financial Highlights**: Period information

## Performance Considerations

### Indexing Strategy

- Real-time indexing on model changes
- Bulk operations for initial data loading
- Configurable batch sizes

### Search Optimization

- Proper field mappings for different data types
- Relevance scoring with fuzzy matching
- Filter-based queries for better performance

### Monitoring

- Index statistics available via service methods
- Logging of all operations
- Error handling and retries

## Troubleshooting

### Common Issues

1. **Connection Failed**
   - Check Elasticsearch is running
   - Verify host/port configuration
   - Check network connectivity

2. **Index Creation Failed**
   - Ensure proper permissions
   - Check disk space
   - Verify mappings are valid

3. **Search Returns No Results**
   - Confirm data is indexed
   - Check search query syntax
   - Verify index exists

### Useful Commands

```bash
# Check index status
curl "localhost:9200/_cat/indices/finboard_*"

# View index mapping
curl "localhost:9200/finboard_pembiayaans/_mapping"

# Search directly in Elasticsearch
curl "localhost:9200/finboard_pembiayaans/_search?q=john"
```

## Future Enhancements

- Search analytics and reporting
- Advanced filtering options
- Search result caching
- Multi-language support
- Autocomplete suggestions
- Search result highlighting
