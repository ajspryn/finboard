#!/usr/bin/env python3

import json
import math
import sys
from dataclasses import dataclass
from typing import Any, Dict, List, Optional, Tuple

import numpy as np
from sklearn.compose import TransformedTargetRegressor
from sklearn.metrics import r2_score
from sklearn.model_selection import GridSearchCV, TimeSeriesSplit
from sklearn.pipeline import Pipeline
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


def _param_grid_for_train_size(train_size: int) -> List[Dict[str, Any]]:
    # Mirror the univariate script philosophy: bounded search on small samples.
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
    X: np.ndarray,
    y: np.ndarray,
    X_next: np.ndarray,
    test_fraction: float,
    non_negative: bool,
    svr_params: Optional[Dict[str, Any]] = None,
) -> ForecastResult:
    warnings: List[str] = []

    if X.ndim != 2:
        return ForecastResult(ok=False, error="X must be 2D")
    if y.ndim != 1:
        return ForecastResult(ok=False, error="y must be 1D")
    if len(X) != len(y):
        return ForecastResult(ok=False, error="X and y length mismatch")

    n = len(y)
    if n < 4:
        return ForecastResult(ok=False, error="not enough samples for training")

    test_size = max(1, int(math.ceil(n * float(test_fraction))))
    train_size = n - test_size
    if train_size < 2:
        return ForecastResult(ok=False, error="train split too small; reduce test_fraction or add more history")

    X_train, y_train = X[:train_size], y[:train_size]
    X_test, y_test = X[train_size:], y[train_size:]

    base = Pipeline(steps=[("x_scaler", MinMaxScaler()), ("svr", SVR())])

    model = TransformedTargetRegressor(regressor=base, transformer=MinMaxScaler())

    best_params = None
    best = model

    if svr_params:
        kernel = str(svr_params.get("kernel", "rbf"))
        C = float(svr_params.get("C", 10.0))
        epsilon = float(svr_params.get("epsilon", 0.1))
        gamma = svr_params.get("gamma", "scale")

        params: Dict[str, Any] = {
            "svr__kernel": kernel,
            "svr__C": C,
            "svr__epsilon": epsilon,
        }
        if kernel in ("rbf", "poly", "sigmoid"):
            params["svr__gamma"] = gamma
        if kernel == "poly":
            if "degree" in svr_params:
                params["svr__degree"] = int(svr_params["degree"])
            if "coef0" in svr_params:
                params["svr__coef0"] = float(svr_params["coef0"])

        best.regressor.set_params(**params)
        best.fit(X_train, y_train)
        warnings.append("fixed-params mode: skipped hyperparameter search")
        best_params = {"regressor__" + k: v for k, v in params.items()}
    elif train_size < 5:
        # Tiny sample: pick a stable default.
        best.regressor.set_params(svr__kernel="rbf", svr__C=10.0, svr__epsilon=0.1, svr__gamma="scale")
        best.fit(X_train, y_train)
        warnings.append("tiny-sample mode: skipped hyperparameter search")
    else:
        n_splits = 2 if train_size < 16 else 3
        tscv = TimeSeriesSplit(n_splits=n_splits)

        # Use MAE on smaller sets; otherwise r2.
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

    # Refit on all samples and forecast.
    best.fit(X, y)
    next_pred = float(best.predict(X_next.reshape(1, -1))[0])
    if non_negative:
        next_pred = max(0.0, next_pred)

    return ForecastResult(
        ok=True,
        prediction=next_pred,
        r2=r2,
        mape=mape,
        train_size=int(train_size),
        test_size=int(test_size),
        best_params=best_params,
        warnings=warnings or [],
    )


def main() -> int:
    try:
        payload = json.loads(sys.stdin.read() or "{}")
    except Exception as e:
        sys.stdout.write(json.dumps({"ok": False, "error": f"invalid json: {e}"}))
        return 2

    X = np.asarray(payload.get("X", []), dtype=np.float64)
    y = np.asarray(payload.get("y", []), dtype=np.float64)
    X_next = np.asarray(payload.get("X_next", []), dtype=np.float64)

    test_fraction = float(payload.get("test_fraction", 0.2))
    non_negative = bool(payload.get("non_negative", False))
    svr_params = payload.get("svr_params")
    if not isinstance(svr_params, dict):
        svr_params = None

    if X_next.ndim != 1 or X_next.size == 0:
        sys.stdout.write(json.dumps({"ok": False, "error": "X_next must be 1D non-empty"}))
        return 2

    res = _fit_and_score(
        X=X,
        y=y,
        X_next=X_next,
        test_fraction=test_fraction,
        non_negative=non_negative,
        svr_params=svr_params,
    )
    out: Dict[str, Any] = {
        "ok": bool(res.ok),
    }
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
