// resources/js/components/Dashboard.tsx
import React, { useEffect } from 'react';
import FinancialHighlights from './FinancialHighlights';
import KPICards from './KPICards';
import RealTimeUpdates from './RealTimeUpdates';
import { useDashboard } from '@/contexts/DashboardContext';

export default function Dashboard() {
  const { loadDashboardData, loadFinancialHighlights } = useDashboard();

  useEffect(() => {
    // Load initial data
    loadDashboardData();
    loadFinancialHighlights();

    // Set up auto-refresh every 5 minutes
    const interval = setInterval(
      () => {
        loadDashboardData();
        loadFinancialHighlights();
      },
      5 * 60 * 1000
    );

    return () => clearInterval(interval);
  }, []);

  return (
    <div className="container-fluid py-4">
      {/* Real-time Updates Component */}
      <RealTimeUpdates />

      {/* Page Header */}
      <div className="row mb-4">
        <div className="col-12">
          <div className="d-flex justify-content-between align-items-center">
            <div>
              <h1 className="mb-1">Dashboard Bank</h1>
              <p className="text-muted mb-0">Monitoring dan analisis kinerja keuangan</p>
            </div>
            <div className="d-flex gap-2">
              <button className="btn btn-outline-primary">
                <i className="ti ti-refresh me-1"></i>Refresh
              </button>
              <button className="btn btn-primary">
                <i className="ti ti-download me-1"></i>Export
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Financial Highlights Section */}
      <FinancialHighlights />

      {/* KPI Cards Section */}
      <div className="row mb-4">
        <div className="col-12">
          <h4 className="mb-3">Key Performance Indicators</h4>
        </div>
      </div>
      <KPICards />

      {/* Additional sections can be added here */}
      <div className="row mb-4">
        <div className="col-12">
          <div className="alert alert-info">
            <i className="ti ti-info-circle me-2"></i>
            <strong>Info:</strong> Dashboard React implementation in progress. Additional components
            (charts, tables, filters) will be added in the next phase.
          </div>
        </div>
      </div>
    </div>
  );
}
