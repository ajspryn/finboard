#!/usr/bin/env python3

import json
import math
import sys
from dataclasses import dataclass
from typing import Any, Dict, List, Tuple

import numpy as np
from sklearn.compose import TransformedTargetRegressor
from sklearn.metrics import r2_score
from sklearn.model_selection import GridSearchCV, TimeSeriesSplit
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVR


@dataclass
class ForecastResult:
    ok: bool
    prediction: float | None = None
    r2: float | None = None
    mape: float | None = None
    train_size: int | None = None
    test_size: int | None = None
    best_params: Dict[str, Any] | None = None
    best_lags: List[int] | None = None
    warnings: List[str] | None = None
    error: str | None = None


def _mape(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    eps = 1e-9
    denom = np.maximum(np.abs(y_true), eps)
    return float(np.mean(np.abs((y_true - y_pred) / denom)) * 100.0)


def _build_supervised(values: np.ndarray, lags: List[int]) -> Tuple[np.ndarray, np.ndarray]:
    max_lag = max(lags)
    X_rows = []
    y_rows = []

    for t in range(max_lag, len(values)):
        X_rows.append([float(values[t - lag]) for lag in lags])
        y_rows.append(float(values[t]))

    return np.array(X_rows, dtype=np.float64), np.array(y_rows, dtype=np.float64)


def _candidate_lag_sets(lags: List[int], max_sets: int) -> List[List[int]]:
    # Generate a small set of lag-subsets to try. Keep deterministic order.
    # Bias toward using recent lags first; always include lag=1 if available.
    base = sorted(set(int(x) for x in lags if int(x) > 0))
    if not base:
        return []

    # Prefer smaller sets on tiny histories.
    preferred = []
    if 1 in base:
        preferred.append([1])
    for k in base:
        if k != 1:
            preferred.append([1, k] if 1 in base else [k])
    # Add a few 3-lag combos.
    recent = base[:]
    recent.sort()
    if 1 in recent and 2 in recent and 3 in recent:
        preferred.append([1, 2, 3])
    if 1 in recent and 2 in recent and 6 in recent:
        preferred.append([1, 2, 6])
    if 1 in recent and 3 in recent and 6 in recent:
        preferred.append([1, 3, 6])

    # De-duplicate while preserving order.
    out: List[List[int]] = []
    seen = set()
    for s in preferred:
        s2 = tuple(sorted(set(int(x) for x in s)))
        if not s2:
            continue
        if s2 in seen:
            continue
        seen.add(s2)
        out.append(list(s2))
        if len(out) >= max_sets:
            break

    return out


def _param_grid_for_train_size(train_size: int) -> List[Dict[str, Any]]:
    # Keep search bounded for small samples to avoid extreme overfitting + runtime.
    if train_size < 10:
        c_vals = [0.5, 1.0, 10.0, 100.0]
        eps_vals = [0.01, 0.1, 0.3, 0.5]
        gamma_vals = ["scale", "auto", 0.1, 0.01]
        return [
            {
                "regressor__svr__kernel": ["rbf"],
                "regressor__svr__C": c_vals,
                "regressor__svr__epsilon": eps_vals,
                "regressor__svr__gamma": gamma_vals,
            },
            {
                "regressor__svr__kernel": ["linear"],
                "regressor__svr__C": c_vals,
                "regressor__svr__epsilon": eps_vals,
            },
        ]

    if train_size < 24:
        c_vals = [0.1, 1.0, 10.0, 100.0, 300.0]
        eps_vals = [0.001, 0.01, 0.1, 0.3, 0.5]
        gamma_vals = ["scale", "auto", 0.3, 0.1, 0.03, 0.01]
        return [
            {
                "regressor__svr__kernel": ["rbf"],
                "regressor__svr__C": c_vals,
                "regressor__svr__epsilon": eps_vals,
                "regressor__svr__gamma": gamma_vals,
            },
            {
                "regressor__svr__kernel": ["linear"],
                "regressor__svr__C": c_vals,
                "regressor__svr__epsilon": eps_vals,
            },
            {
                "regressor__svr__kernel": ["poly"],
                "regressor__svr__C": [0.1, 1.0, 10.0, 100.0],
                "regressor__svr__epsilon": [0.01, 0.1, 0.3],
                "regressor__svr__gamma": ["scale", "auto", 0.1, 0.01],
                "regressor__svr__degree": [2, 3],
                "regressor__svr__coef0": [0.0, 0.5, 1.0],
            },
        ]

    # Larger history: broader search.
    return [
        {
            "regressor__svr__kernel": ["rbf"],
            "regressor__svr__C": [0.1, 1.0, 10.0, 100.0, 300.0, 1000.0],
            "regressor__svr__epsilon": [0.001, 0.01, 0.1, 0.3, 0.5, 1.0],
            "regressor__svr__gamma": ["scale", "auto", 1.0, 0.3, 0.1, 0.03, 0.01, 0.003],
        },
        {
            "regressor__svr__kernel": ["linear"],
            "regressor__svr__C": [0.1, 1.0, 10.0, 100.0, 300.0, 1000.0],
            "regressor__svr__epsilon": [0.001, 0.01, 0.1, 0.3, 0.5, 1.0],
        },
        {
            "regressor__svr__kernel": ["poly"],
            "regressor__svr__C": [0.1, 1.0, 10.0, 100.0, 300.0],
            "regressor__svr__epsilon": [0.001, 0.01, 0.1, 0.3],
            "regressor__svr__gamma": ["scale", "auto", 0.3, 0.1, 0.03, 0.01],
            "regressor__svr__degree": [2, 3, 4],
            "regressor__svr__coef0": [0.0, 0.5, 1.0],
        },
        {
            "regressor__svr__kernel": ["sigmoid"],
            "regressor__svr__C": [0.1, 1.0, 10.0, 100.0],
            "regressor__svr__epsilon": [0.001, 0.01, 0.1, 0.3],
            "regressor__svr__gamma": ["scale", "auto", 0.3, 0.1, 0.03, 0.01],
            "regressor__svr__coef0": [0.0, 0.5, 1.0],
        },
    ]


def _fit_and_score(
    metric: str,
    values: np.ndarray,
    lags: List[int],
    test_fraction: float,
    non_negative: bool,
) -> Tuple[ForecastResult, Any]:
    warnings: List[str] = []

    max_lag = max(lags)
    min_points = max_lag + 3
    if len(values) < min_points:
        return (
            ForecastResult(ok=False, error=f"insufficient history for lags={lags}; need at least {min_points} points"),
            None,
        )

    X, y = _build_supervised(values, lags)
    if len(y) < 3:
        return (ForecastResult(ok=False, error="not enough supervised samples after lag transform"), None)

    n = len(y)
    test_size = max(1, int(math.ceil(n * test_fraction)))
    train_size = n - test_size
    if train_size < 2:
        return (
            ForecastResult(ok=False, error="train split too small; reduce test_fraction or add more history"),
            None,
        )

    X_train, y_train = X[:train_size], y[:train_size]
    X_test, y_test = X[train_size:], y[train_size:]

    base = Pipeline(
        steps=[
            ("x_scaler", StandardScaler()),
            ("svr", SVR()),
        ]
    )

    model = TransformedTargetRegressor(
        regressor=base,
        transformer=StandardScaler(),
    )

    best_params = None
    best = model

    # Always prefer some tuning when possible; keep folds conservative on tiny data.
    if train_size < 5:
        best.regressor.set_params(svr__kernel="rbf", svr__C=10.0, svr__epsilon=0.1, svr__gamma="scale")
        best.fit(X_train, y_train)
        warnings.append("tiny-sample mode: skipped hyperparameter search")
    else:
        n_splits = 2 if train_size < 16 else 3
        tscv = TimeSeriesSplit(n_splits=n_splits)

        # R² is unstable on tiny folds; use MAE to pick a reasonable model.
        scoring = "neg_mean_absolute_error" if train_size < 24 else "r2"

        param_grid = _param_grid_for_train_size(train_size)

        search = GridSearchCV(
            estimator=model,
            param_grid=param_grid,
            cv=tscv,
            scoring=scoring,
            n_jobs=1,
            error_score="raise",
            refit=True,
        )

        search.fit(X_train, y_train)
        best = search.best_estimator_
        best_params = search.best_params_

    # Holdout evaluation.
    y_pred = best.predict(X_test)
    r2 = float(r2_score(y_test, y_pred)) if len(y_test) >= 2 else None
    mape = float(_mape(y_test, y_pred))

    # IMPORTANT: for the actual forecast, refit on ALL available samples up to cutoff.
    # Otherwise the most recent points (held out as test) are ignored for prediction.
    best.fit(X, y)

    last_features = np.array([[float(values[-lag]) for lag in lags]], dtype=np.float64)
    next_pred = float(best.predict(last_features)[0])
    if non_negative or metric.lower() == "npf_ratio":
        next_pred = max(0.0, next_pred)

    return (
        ForecastResult(
            ok=True,
            prediction=next_pred,
            r2=r2,
            mape=mape,
            train_size=int(train_size),
            test_size=int(test_size),
            best_params=best_params,
            best_lags=lags,
            warnings=warnings or [],
        ),
        best,
    )


def _next_period(year: int, month: int) -> Tuple[int, int]:
    if month == 12:
        return year + 1, 1
    return year, month + 1


def run_forecast(payload: Dict[str, Any]) -> ForecastResult:
    metric = str(payload.get("metric", ""))
    series = payload.get("series")
    lags = payload.get("lags")
    test_fraction = float(payload.get("test_fraction", 0.2))
    lag_search = bool(payload.get("lag_search", False))
    lag_search_max_sets = int(payload.get("lag_search_max_sets", 6))
    non_negative = bool(payload.get("non_negative", False))

    warnings: List[str] = []

    if not isinstance(series, list) or not series:
        return ForecastResult(ok=False, error="series must be a non-empty list")

    if not isinstance(lags, list) or not lags or not all(isinstance(x, int) and x > 0 for x in lags):
        return ForecastResult(ok=False, error="lags must be a non-empty list of positive integers")

    # Extract & validate.
    points: List[Tuple[int, int, float]] = []
    for i, row in enumerate(series):
        if not isinstance(row, dict):
            return ForecastResult(ok=False, error=f"series[{i}] must be an object")
        try:
            year = int(row["year"])
            month = int(row["month"])
            value = float(row["value"])
        except Exception:
            return ForecastResult(ok=False, error=f"series[{i}] must have year, month, value")
        if year <= 0 or month < 1 or month > 12:
            return ForecastResult(ok=False, error=f"invalid period in series[{i}]")
        if not math.isfinite(value):
            return ForecastResult(ok=False, error=f"non-finite value in series[{i}]")
        points.append((year, month, value))

    # Sort by period.
    points.sort(key=lambda x: (x[0], x[1]))

    # Warn on gaps.
    for idx in range(1, len(points)):
        prev_y, prev_m, _ = points[idx - 1]
        y, m, _ = points[idx]
        exp_y, exp_m = _next_period(prev_y, prev_m)
        if (y, m) != (exp_y, exp_m):
            warnings.append("series has period gaps; treating as sequential observations")
            break

    values = np.array([p[2] for p in points], dtype=np.float64)

    # Optionally search across a small number of lag-subsets.
    candidate_sets: List[List[int]]
    if lag_search:
        candidate_sets = _candidate_lag_sets([int(x) for x in lags], max_sets=max(1, lag_search_max_sets))
        if not candidate_sets:
            candidate_sets = [sorted(set(int(x) for x in lags))]
        warnings.append(f"lag_search enabled: trying {len(candidate_sets)} lag-set(s)")
    else:
        candidate_sets = [sorted(set(int(x) for x in lags))]

    best_result: ForecastResult | None = None
    best_mape: float | None = None
    best_error: str | None = None

    for lag_set in candidate_sets:
        res, _ = _fit_and_score(metric=metric, values=values, lags=lag_set, test_fraction=test_fraction, non_negative=non_negative)
        if not res.ok:
            best_error = res.error
            continue

        # Prefer lower holdout MAPE.
        if best_result is None:
            best_result = res
            best_mape = res.mape
            continue

        if res.mape is not None and (best_mape is None or res.mape < best_mape):
            best_result = res
            best_mape = res.mape

    if best_result is None:
        return ForecastResult(ok=False, error=best_error or "forecast failed")

    # Merge warnings.
    best_result.warnings = (warnings or []) + (best_result.warnings or [])
    return best_result


def main() -> int:
    try:
        raw = sys.stdin.read()
        payload = json.loads(raw) if raw.strip() else {}
    except Exception as e:
        sys.stdout.write(json.dumps({"ok": False, "error": f"invalid json input: {e}"}))
        return 2

    try:
        result = run_forecast(payload)
    except Exception as e:
        sys.stdout.write(json.dumps({"ok": False, "error": f"runtime error: {e}"}))
        return 3

    out = {
        "ok": result.ok,
        "prediction": result.prediction,
        "r2": result.r2,
        "mape": result.mape,
        "train_size": result.train_size,
        "test_size": result.test_size,
        "best_params": result.best_params,
        "best_lags": result.best_lags,
        "warnings": result.warnings,
        "error": result.error,
    }

    sys.stdout.write(json.dumps(out))
    return 0 if result.ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
