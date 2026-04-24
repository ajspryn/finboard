#!/usr/bin/env python3

import json
import math
import sys
from dataclasses import dataclass
from datetime import date
from typing import Any, Dict, List, Optional, Tuple

import numpy as np
from sklearn.metrics import r2_score
from sklearn.preprocessing import MinMaxScaler
from sklearn.svm import SVR


def _mape(y_true: np.ndarray, y_pred: np.ndarray) -> float:
    y_true = np.asarray(y_true, dtype=np.float64)
    y_pred = np.asarray(y_pred, dtype=np.float64)
    eps = 1e-9
    denom = np.maximum(np.abs(y_true), eps)
    return float(np.mean(np.abs((y_true - y_pred) / denom)) * 100.0)


@dataclass
class ForecastResult:
    ok: bool
    prediction: Optional[float] = None
    r2: Optional[float] = None
    mape: Optional[float] = None
    train_size: Optional[int] = None
    test_size: Optional[int] = None
    best_params: Optional[Dict[str, Any]] = None
    warnings: Optional[List[str]] = None
    error: Optional[str] = None


def _to_ordinal(year: int, month: int) -> int:
    # PDF: Bulan_Num = to_ordinal(Bulan) with YYYY-MM-DD.
    return int(date(int(year), int(month), 1).toordinal())


def _build_matrix(rows: List[Dict[str, Any]]) -> np.ndarray:
    data: List[List[float]] = []
    for r in rows:
        year = int(r["year"])
        month = int(r["month"])
        month_num = float(_to_ordinal(year, month))

        npf_ratio = float(r.get("npf_ratio", 0.0))
        outstanding = float(r.get("outstanding", 0.0))
        tunggakan_pokok = float(r.get("tunggakan_pokok", 0.0))
        rate_eff = float(r.get("rate_eff", 0.0))

        data.append([month_num, npf_ratio, outstanding, tunggakan_pokok, rate_eff])

    return np.asarray(data, dtype=np.float64)


def _build_sliding_window(data_scaled: np.ndarray, window_size: int, target_col: int) -> Tuple[np.ndarray, np.ndarray]:
    if window_size < 1:
        raise ValueError("window_size must be >= 1")
    if data_scaled.ndim != 2:
        raise ValueError("data_scaled must be 2D")

    n = data_scaled.shape[0]
    n_features = data_scaled.shape[1]

    X_rows: List[List[float]] = []
    y_rows: List[float] = []

    for i in range(window_size, n):
        window = data_scaled[i - window_size : i, :]
        X_rows.append(window.reshape(window_size * n_features).tolist())
        y_rows.append(float(data_scaled[i, target_col]))

    return np.asarray(X_rows, dtype=np.float64), np.asarray(y_rows, dtype=np.float64)


def _inverse_single(scaler: MinMaxScaler, col_idx: int, y_scaled: float, n_features: int) -> float:
    dummy = np.zeros((1, n_features), dtype=np.float64)
    dummy[0, col_idx] = float(y_scaled)
    inv = scaler.inverse_transform(dummy)
    return float(inv[0, col_idx])


def _fit_and_forecast(
    data: np.ndarray,
    window_size: int,
    test_fraction: float,
    svr_params: Dict[str, Any],
    non_negative: bool,
) -> ForecastResult:
    warnings: List[str] = []

    if data.ndim != 2 or data.shape[1] < 2:
        return ForecastResult(ok=False, error="data must be 2D with >=2 columns")

    # Column order matches PDF NPF section: [Bulan_Num, NPF, Outstanding, Tunggakan_Pokok, Rate_Efektif]
    target_col = 1

    if data.shape[0] < window_size + 2:
        return ForecastResult(ok=False, error="not enough monthly rows for sliding window")

    scaler = MinMaxScaler()
    data_scaled = scaler.fit_transform(data)

    X, y = _build_sliding_window(data_scaled, window_size=window_size, target_col=target_col)
    if X.shape[0] < 3:
        return ForecastResult(ok=False, error="not enough samples after sliding-window transform")

    n = X.shape[0]
    test_size = max(1, int(math.ceil(n * float(test_fraction))))
    train_size = n - test_size
    if train_size < 2:
        return ForecastResult(ok=False, error="train split too small; reduce test_fraction or add more history")

    X_train, y_train = X[:train_size], y[:train_size]
    X_test, y_test = X[train_size:], y[train_size:]

    kernel = str(svr_params.get("kernel", "rbf"))
    C = float(svr_params.get("C", 100.0))
    gamma = svr_params.get("gamma", 0.1)
    epsilon = float(svr_params.get("epsilon", 0.01))

    model = SVR(kernel=kernel, C=C, gamma=gamma, epsilon=epsilon)
    model.fit(X_train, y_train)

    y_test_pred = model.predict(X_test)

    # Convert test predictions back to real scale for metrics.
    n_features = data.shape[1]
    y_test_real = np.asarray([_inverse_single(scaler, target_col, v, n_features) for v in y_test], dtype=np.float64)
    y_pred_real = np.asarray([_inverse_single(scaler, target_col, v, n_features) for v in y_test_pred], dtype=np.float64)

    r2 = float(r2_score(y_test_real, y_pred_real)) if len(y_test_real) >= 2 else None
    mape = float(_mape(y_test_real, y_pred_real))

    # Refit on all samples then forecast next point using last `window_size` months.
    model.fit(X, y)
    last_window = data_scaled[-window_size:, :].reshape(1, -1)
    next_scaled = float(model.predict(last_window)[0])
    next_real = _inverse_single(scaler, target_col, next_scaled, n_features)

    if non_negative:
        next_real = max(0.0, next_real)

    # NPF ratio is percentage; cap to [0, 100] as a safety rail.
    next_real = max(0.0, min(100.0, float(next_real)))

    return ForecastResult(
        ok=True,
        prediction=float(next_real),
        r2=r2,
        mape=mape,
        train_size=int(train_size),
        test_size=int(test_size),
        best_params={
            "kernel": kernel,
            "C": C,
            "gamma": gamma,
            "epsilon": epsilon,
            "window_size": int(window_size),
        },
        warnings=warnings,
    )


def main() -> int:
    try:
        payload = json.loads(sys.stdin.read() or "{}")
    except Exception as e:
        sys.stdout.write(json.dumps({"ok": False, "error": f"invalid json: {e}"}))
        return 2

    rows = payload.get("rows", [])
    if not isinstance(rows, list) or not rows:
        sys.stdout.write(json.dumps({"ok": False, "error": "rows must be a non-empty list"}))
        return 0

    window_size = int(payload.get("window_size", 3))
    test_fraction = float(payload.get("test_fraction", 0.2))
    non_negative = bool(payload.get("non_negative", False))

    svr_params = payload.get("svr_params")
    if not isinstance(svr_params, dict):
        svr_params = {"kernel": "rbf", "C": 100.0, "gamma": 0.1, "epsilon": 0.01}

    data = _build_matrix(rows)

    res = _fit_and_forecast(
        data=data,
        window_size=window_size,
        test_fraction=test_fraction,
        svr_params=svr_params,
        non_negative=non_negative,
    )

    out: Dict[str, Any] = {"ok": bool(res.ok)}
    if not res.ok:
        out["error"] = res.error or "unknown error"
        sys.stdout.write(json.dumps(out))
        return 0

    out.update(
        {
            "prediction": res.prediction,
            "r2": res.r2,
            "mape": res.mape,
            "train_size": res.train_size,
            "test_size": res.test_size,
            "best_params": res.best_params,
            "warnings": res.warnings or [],
        }
    )

    sys.stdout.write(json.dumps(out))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
