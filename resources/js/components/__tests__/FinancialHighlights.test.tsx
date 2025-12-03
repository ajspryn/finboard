// resources/js/components/__tests__/FinancialHighlights.test.tsx
import { render, screen, waitFor } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';
import FinancialHighlights from '../FinancialHighlights';
import { DashboardProvider } from '@/contexts/DashboardContext';

// Mock the dashboard context
const mockUseDashboard = vi.fn();
vi.mock('@/contexts/DashboardContext', () => ({
  useDashboard: () => mockUseDashboard(),
  DashboardProvider: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

describe('FinancialHighlights', () => {
  it('renders loading state initially', () => {
    mockUseDashboard.mockReturnValue({
      state: {
        financialHighlights: null,
        filters: { comparison: 'MOM' },
      },
      loadFinancialHighlights: vi.fn(),
    });

    render(
      <DashboardProvider>
        <FinancialHighlights />
      </DashboardProvider>
    );

    expect(screen.getByText('Memuat data financial highlights...')).toBeInTheDocument();
  });

  it('renders financial highlights data when loaded', async () => {
    const mockData = {
      period_year: 2025,
      period_month: 12,
      car: 15.5,
      roa: 2.1,
      npf: 3.2,
    };

    mockUseDashboard.mockReturnValue({
      state: {
        financialHighlights: mockData,
        filters: { comparison: 'MOM' },
      },
      loadFinancialHighlights: vi.fn(),
    });

    render(
      <DashboardProvider>
        <FinancialHighlights />
      </DashboardProvider>
    );

    await waitFor(() => {
      expect(screen.getByText('Financial Highlights')).toBeInTheDocument();
      expect(screen.getByText('Periode: 2025-12')).toBeInTheDocument();
    });
  });

  it('handles comparison type toggle', async () => {
    mockUseDashboard.mockReturnValue({
      state: {
        financialHighlights: {
          period_year: 2025,
          period_month: 12,
          car: 15.5,
        },
        filters: { comparison: 'MOM' },
      },
      loadFinancialHighlights: vi.fn(),
    });

    render(
      <DashboardProvider>
        <FinancialHighlights />
      </DashboardProvider>
    );

    const yoyButton = screen.getByText('YOY');
    expect(yoyButton).toBeInTheDocument();
  });
});
