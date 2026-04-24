<?php

return [
     /*
    |--------------------------------------------------------------------------
    | SVR Predictions (Python)
    |--------------------------------------------------------------------------
    |
    | This app can run Support Vector Regression (SVR) forecasts using a Python
    | virtualenv located at ml/.venv.
    |
    | Set PREDICTIONS_PYTHON_BIN in .env if you want to override.
    |
    */

     'python_bin' => env('PREDICTIONS_PYTHON_BIN', base_path('ml/.venv/bin/python')),

     'svr' => [
          // Lag features (months) used to build supervised learning rows.
          'lags' => [1, 2, 3, 6, 12],

          // Use only the most recent N months as the training window.
          // This keeps the model focused on the latest regime.
          // Set to 0 to disable windowing and use up to max_months.
          'train_window_months' => 6,

          // Do not run SVR if less than this many months exist in history.
          // Requirement: minimal 6 months.
          'min_history_months' => 6,

          // Try multiple lag-subsets and pick the one with lowest holdout MAPE.
          // Helps small datasets where one fixed lag-set can be unstable.
          'lag_search' => true,
          'lag_search_max_sets' => 6,

          // Holdout test split as a fraction of samples.
          'test_fraction' => 0.2,

          // How many historical months to use at most per metric.
          'max_months' => 60,

          // For NPF-related metrics, a shorter history window can help after regime shifts.
          // This is a pragmatic knob; increase if you have longer stable history.
          'npf_max_months' => 6,

          // Accuracy gate (cannot be guaranteed; used for reporting).
          'target_r2' => 0.90,
     ],
];
