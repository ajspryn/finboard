// resources/js/components/KPICards.tsx
import React from 'react';
import { useDashboard } from '@/contexts/DashboardContext';

const formatNominal = (amount: number): string => {
  if (amount >= 1000000000) {
    return `Rp ${(amount / 1000000000).toFixed(2)}M`;
  } else if (amount >= 1000000) {
    return `Rp ${(amount / 1000000).toFixed(2)}Jt`;
  } else if (amount >= 100000) {
    return `Rp ${(amount / 1000).toFixed(0)}Rb`;
  } else if (amount >= 1000) {
    return `Rp ${(amount / 1000).toFixed(1)}Rb`;
  } else {
    return `Rp ${amount.toLocaleString()}`;
  }
};

interface KPICardProps {
  title: string;
  subtitle: string;
  value: number;
  growth: number;
  icon: string;
  color: string;
  onClick?: () => void;
}

const KPICard: React.FC<KPICardProps> = ({
  title,
  subtitle,
  value,
  growth,
  icon,
  color,
  onClick,
}) => {
  const growthColor = growth >= 0 ? 'success' : 'danger';
  const growthIcon = growth >= 0 ? 'ti-trending-up' : 'ti-trending-down';

  return (
    <div className="col-lg-4 col-md-6 col-12 mb-4">
      <div
        className={`card h-100 border-${color} border-2 ${onClick ? 'clickable-metric' : ''}`}
        onClick={onClick}
        style={{ cursor: onClick ? 'pointer' : 'default' }}
      >
        <div className={`card-header d-flex justify-content-between bg-label-${color}`}>
          <div className="card-title mb-0">
            <h5 className={`mb-0 text-${color}`}>{title}</h5>
            <small className="text-muted">{subtitle}</small>
          </div>
          <div className="dropdown">
            <span className={`badge bg-${growthColor}`}>
              {growth >= 0 ? '+' : ''}
              {growth}%
            </span>
          </div>
        </div>
        <div className="card-body">
          <div className="d-flex justify-content-between align-items-center mb-3">
            <div className="d-flex flex-column">
              <div className="d-flex align-items-center mb-1">
                <h2 className="mb-0 me-2 text-info fw-bold">{formatNominal(value)}</h2>
              </div>
              <small className={`text-${growthColor} fw-medium`}>
                <i className={`ti ${growthIcon} ti-sm`}></i>
                <span>Pertumbuhan {growth}%</span>
              </small>
            </div>
            <div className="avatar avatar-lg">
              <span className={`avatar-initial rounded-3 bg-${color}`}>
                <i className={`ti ${icon} ti-lg text-white`}></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default function KPICards() {
  const { state } = useDashboard();

  if (!state.data) {
    return (
      <div className="row">
        {[1, 2, 3].map(i => (
          <div key={i} className="col-lg-4 col-md-6 col-12 mb-4">
            <div className="card h-100">
              <div className="card-body">
                <div
                  className="d-flex justify-content-center align-items-center"
                  style={{ height: '120px' }}
                >
                  <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  const handleFundingClick = () => {
    // Navigate to funding details or open modal
    console.log('Funding details clicked');
  };

  const handleLendingClick = () => {
    // Navigate to lending details or open modal
    console.log('Lending details clicked');
  };

  const handleNPFClick = () => {
    // Navigate to NPF details or open modal
    console.log('NPF details clicked');
  };

  return (
    <div className="row">
      <KPICard
        title="💰 Funding"
        subtitle="Dana Pihak Ketiga"
        value={state.data.funding.total}
        growth={state.data.funding.growth}
        icon="ti-coin"
        color="info"
        onClick={handleFundingClick}
      />

      <KPICard
        title="💳 Lending"
        subtitle="Pembiayaan"
        value={state.data.lending.total}
        growth={state.data.lending.growth}
        icon="ti-cash"
        color="success"
        onClick={handleLendingClick}
      />

      <KPICard
        title="📊 NPF"
        subtitle="Non-Performing Financing"
        value={state.data.npf.ratio}
        growth={state.data.npf.growth}
        icon="ti-alert-triangle"
        color="danger"
        onClick={handleNPFClick}
      />
    </div>
  );
}
