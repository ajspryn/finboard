// resources/js/components/FinancialHighlights.tsx
import React, { useEffect, useState } from 'react';
import { useDashboard } from '@/contexts/DashboardContext';
import { FinancialHighlight } from '@/types';

interface IndicatorConfig {
  key: keyof FinancialHighlight;
  label: string;
  unit: string;
  icon: string;
  color: string;
  format?: 'currency' | 'percentage';
  reverseColor?: boolean;
  badWhenUp?: boolean;
}

const indicators: IndicatorConfig[] = [
  // Rasio Modal & Kesehatan
  {
    key: 'car',
    label: 'CAR',
    unit: '%',
    icon: 'ti-shield-check',
    color: 'primary',
    reverseColor: true,
  },
  {
    key: 'kpmm',
    label: 'KPMM',
    unit: 'Rp',
    icon: 'ti-currency-dollar',
    color: 'info',
    format: 'currency',
  },

  // Rasio Profitabilitas
  {
    key: 'roa',
    label: 'ROA',
    unit: '%',
    icon: 'ti-trending-up',
    color: 'success',
    reverseColor: true,
  },
  { key: 'roe', label: 'ROE', unit: '%', icon: 'ti-chart-bar', color: 'info', reverseColor: true },

  // Rasio Likuiditas
  { key: 'cash_ratio', label: 'Cash Ratio', unit: '%', icon: 'ti-cash-banknote', color: 'success' },

  // Rasio Risiko
  {
    key: 'npf',
    label: 'NPF',
    unit: '%',
    icon: 'ti-alert-triangle',
    color: 'danger',
    badWhenUp: true,
  },
  { key: 'fdr', label: 'FDR', unit: '%', icon: 'ti-percentage', color: 'warning', badWhenUp: true },

  // Rasio Efisiensi
  {
    key: 'bopo',
    label: 'BOPO',
    unit: '%',
    icon: 'ti-calculator',
    color: 'secondary',
    badWhenUp: true,
  },

  // Posisi Keuangan
  {
    key: 'aset',
    label: 'Aset',
    unit: 'Rp',
    icon: 'ti-building-bank',
    color: 'warning',
    format: 'currency',
  },
  { key: 'dpk', label: 'DPK', unit: 'Rp', icon: 'ti-wallet', color: 'primary', format: 'currency' },
  {
    key: 'pembiayaan',
    label: 'Pembiayaan',
    unit: 'Rp',
    icon: 'ti-cash',
    color: 'danger',
    format: 'currency',
  },

  // Laba & Biaya
  {
    key: 'laba_rugi',
    label: 'Laba/Rugi',
    unit: 'Rp',
    icon: 'ti-coins',
    color: 'success',
    format: 'currency',
  },
  {
    key: 'biaya',
    label: 'Biaya',
    unit: 'Rp',
    icon: 'ti-receipt',
    color: 'dark',
    format: 'currency',
  },
];

const formatValue = (value: number | undefined, format?: string): string => {
  if (value === undefined || value === null) return '-';

  switch (format) {
    case 'currency':
      if (value >= 1000000000) {
        return `Rp ${(value / 1000000000).toFixed(2)}M`;
      } else if (value >= 1000000) {
        return `Rp ${(value / 1000000).toFixed(2)}Jt`;
      } else if (value >= 1000) {
        return `Rp ${(value / 1000).toFixed(1)}Rb`;
      }
      return `Rp ${value.toLocaleString()}`;

    case 'percentage':
      return `${value.toFixed(2)}%`;

    default:
      return value.toLocaleString();
  }
};

const getIndicatorColor = (
  config: IndicatorConfig,
  value: number | undefined,
  change?: number
): string => {
  if (value === undefined || value === null) return 'secondary';

  if (config.badWhenUp && change !== undefined) {
    return change > 0 ? 'danger' : change < 0 ? 'success' : 'warning';
  }

  if (config.reverseColor) {
    if (value < 0) return 'danger';
    if (value < 5) return 'warning';
    return 'success';
  }

  // Default logic for good indicators
  if (value > 0) return 'success';
  if (value < 0) return 'danger';
  return 'warning';
};

export default function FinancialHighlights() {
  const { state, loadFinancialHighlights } = useDashboard();
  const [comparisonType, setComparisonType] = useState<'MOM' | 'YOY'>('MOM');

  useEffect(() => {
    loadFinancialHighlights();
  }, [state.filters]);

  const handleComparisonChange = (type: 'MOM' | 'YOY') => {
    setComparisonType(type);
    // Update filters in context
  };

  if (!state.financialHighlights) {
    return (
      <div className="card border-primary border-2">
        <div className="card-body text-center py-4">
          <div className="spinner-border text-primary" role="status">
            <span className="visually-hidden">Loading...</span>
          </div>
          <p className="text-muted mt-2">Memuat data financial highlights...</p>
        </div>
      </div>
    );
  }

  const leftColumn = indicators.slice(0, 4);
  const centerColumn = indicators.slice(4, 9);
  const rightColumn = indicators.slice(9, 13);

  const renderIndicatorCard = (indicator: IndicatorConfig) => {
    const value = state.financialHighlights?.[indicator.key];
    const color = getIndicatorColor(indicator, value);

    return (
      <div key={indicator.key} className="col-md-6 col-lg-4 mb-3">
        <div className="card h-100 border-0 shadow-sm">
          <div className="card-body">
            <div className="d-flex justify-content-between align-items-start mb-2">
              <div className={`avatar avatar-sm bg-label-${color} rounded`}>
                <i className={`ti ${indicator.icon} ti-sm text-${color}`}></i>
              </div>
              <small className="text-muted">{indicator.label}</small>
            </div>
            <div className="d-flex flex-column">
              <h6 className={`mb-1 text-${color} fw-bold`}>
                {formatValue(value, indicator.format)} {indicator.unit}
              </h6>
              <small className="text-muted">Current Period</small>
            </div>
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="row mb-4">
      <div className="col-12">
        <div className="card border-primary border-2">
          <div className="card-header d-flex justify-content-between align-items-center bg-label-primary">
            <div className="card-title mb-0">
              <h4 className="mb-0 text-primary">
                <i className="ti ti-chart-line me-2"></i>
                Financial Highlights
              </h4>
              <small className="text-muted">Indikator Kinerja Keuangan Terbaru</small>
            </div>
            <div className="d-flex gap-2 align-items-center">
              <div className="btn-group btn-group-sm" role="group">
                <button
                  type="button"
                  className={`btn ${comparisonType === 'MOM' ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => handleComparisonChange('MOM')}
                >
                  <i className="ti ti-calendar-month me-1"></i>MOM
                </button>
                <button
                  type="button"
                  className={`btn ${comparisonType === 'YOY' ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => handleComparisonChange('YOY')}
                >
                  <i className="ti ti-calendar-year me-1"></i>YOY
                </button>
              </div>
            </div>
          </div>
          <div className="card-body">
            <div className="row g-3">
              <div className="col-12 mb-2">
                <div className="d-flex justify-content-between align-items-center">
                  <h6 className="mb-0 text-muted">
                    Periode: {state.financialHighlights.period_year}-
                    {String(state.financialHighlights.period_month).padStart(2, '0')}
                  </h6>
                  <small className="text-muted">Perbandingan: {comparisonType}</small>
                </div>
              </div>

              <div className="col-lg-12">
                <div className="row g-3">
                  {/* Left Column */}
                  <div className="col-lg-4">
                    <div className="row g-3">{leftColumn.map(renderIndicatorCard)}</div>
                  </div>

                  {/* Center Column */}
                  <div className="col-lg-4">
                    <div className="row g-3">{centerColumn.map(renderIndicatorCard)}</div>
                  </div>

                  {/* Right Column */}
                  <div className="col-lg-4">
                    <div className="row g-3">{rightColumn.map(renderIndicatorCard)}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
