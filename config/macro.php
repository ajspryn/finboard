<?php

return [
     // Official (requires API key): https://webapi.bps.go.id
     'bps' => [
          'base_url' => env('BPS_API_BASE_URL', 'https://webapi.bps.go.id/v1/api'),
          'api_key' => env('BPS_API_KEY'),
          'timeout_seconds' => (int) env('BPS_TIMEOUT_SECONDS', 20),

          // SDDS variable id: Consumer Price Index of 90 City (General)
          // We use CPI to compute inflation YoY: (CPI_t / CPI_{t-12} - 1) * 100
          'cpi_var_id' => (int) env('BPS_CPI_VAR_ID', 1709),

          // Prefer Indonesia aggregate (vervar label is commonly "INDONESIA").
          'vervar_preferred_label' => env('BPS_VERVAR_PREFERRED_LABEL', 'INDONESIA'),

          // Prefer "Umum"/"General" when turvar exists.
          'turvar_prefer_contains' => env('BPS_TURVAR_PREFER_CONTAINS', 'UMUM'),
     ],

     'fred' => [
          'base_url' => env('FRED_CSV_BASE_URL', 'https://fred.stlouisfed.org/graph/fredgraph.csv'),

          // Indonesia: Interest Rates: Immediate Rates (< 24 Hours): Central Bank Rates: Total
          // (Used as a practical proxy for BI policy/central bank rate, monthly)
          'bi_rate_series_id' => env('FRED_BI_RATE_SERIES_ID', 'IRSTCB01IDM156N'),

          // Indonesia: CPI inflation YoY (growth rate same period previous year), monthly
          'inflation_yoy_series_id' => env('FRED_INFLATION_YOY_SERIES_ID', 'CPALTT01IDM659N'),

          'timeout_seconds' => (int) env('FRED_TIMEOUT_SECONDS', 20),
     ],
];
