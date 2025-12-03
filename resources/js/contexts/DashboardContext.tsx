// resources/js/contexts/DashboardContext.tsx
import React, { createContext, useContext, useReducer, ReactNode } from 'react';
import { DashboardData, FilterOptions, FinancialHighlight } from '@/types';

interface DashboardState {
  data: DashboardData | null;
  financialHighlights: FinancialHighlight | null;
  filters: FilterOptions;
  loading: boolean;
  error: string | null;
}

type DashboardAction =
  | { type: 'SET_LOADING'; payload: boolean }
  | { type: 'SET_DATA'; payload: DashboardData }
  | { type: 'SET_FINANCIAL_HIGHLIGHTS'; payload: FinancialHighlight }
  | { type: 'SET_FILTERS'; payload: Partial<FilterOptions> }
  | { type: 'SET_ERROR'; payload: string | null }
  | { type: 'RESET' };

const initialState: DashboardState = {
  data: null,
  financialHighlights: null,
  filters: {
    comparison: 'MOM',
  },
  loading: false,
  error: null,
};

function dashboardReducer(state: DashboardState, action: DashboardAction): DashboardState {
  switch (action.type) {
    case 'SET_LOADING':
      return { ...state, loading: action.payload };
    case 'SET_DATA':
      return { ...state, data: action.payload, loading: false, error: null };
    case 'SET_FINANCIAL_HIGHLIGHTS':
      return { ...state, financialHighlights: action.payload };
    case 'SET_FILTERS':
      return { ...state, filters: { ...state.filters, ...action.payload } };
    case 'SET_ERROR':
      return { ...state, error: action.payload, loading: false };
    case 'RESET':
      return initialState;
    default:
      return state;
  }
}

interface DashboardContextType {
  state: DashboardState;
  dispatch: React.Dispatch<DashboardAction>;
  updateFilters: (filters: Partial<FilterOptions>) => void;
  loadDashboardData: () => Promise<void>;
  loadFinancialHighlights: () => Promise<void>;
}

const DashboardContext = createContext<DashboardContextType | undefined>(undefined);

export function DashboardProvider({ children }: { children: ReactNode }) {
  const [state, dispatch] = useReducer(dashboardReducer, initialState);

  const updateFilters = (filters: Partial<FilterOptions>) => {
    dispatch({ type: 'SET_FILTERS', payload: filters });
  };

  const loadDashboardData = async () => {
    dispatch({ type: 'SET_LOADING', payload: true });
    try {
      // This would be replaced with actual API call
      const response = await fetch('/api/dashboard-data');
      const data = await response.json();
      dispatch({ type: 'SET_DATA', payload: data });
    } catch (error) {
      dispatch({ type: 'SET_ERROR', payload: 'Failed to load dashboard data' });
    }
  };

  const loadFinancialHighlights = async () => {
    try {
      const queryParams = new URLSearchParams({
        comparison: state.filters.comparison || 'MOM',
        ...(state.filters.month && { month: state.filters.month.toString() }),
        ...(state.filters.year && { year: state.filters.year.toString() }),
      });

      const response = await fetch(`/api/financial-highlights/dashboard?${queryParams}`);
      const data = await response.json();

      if (data.data) {
        dispatch({ type: 'SET_FINANCIAL_HIGHLIGHTS', payload: data.data });
      }
    } catch (error) {
      console.error('Failed to load financial highlights:', error);
    }
  };

  return (
    <DashboardContext.Provider
      value={{
        state,
        dispatch,
        updateFilters,
        loadDashboardData,
        loadFinancialHighlights,
      }}
    >
      {children}
    </DashboardContext.Provider>
  );
}

export function useDashboard() {
  const context = useContext(DashboardContext);
  if (context === undefined) {
    throw new Error('useDashboard must be used within a DashboardProvider');
  }
  return context;
}
