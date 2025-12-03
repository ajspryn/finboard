// resources/js/types/index.ts
export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'pengurus' | 'funding' | 'lending';
}

export interface FinancialHighlight {
  id?: number;
  period_year: number;
  period_month: number;
  car?: number;
  roa?: number;
  roe?: number;
  aset?: number;
  pembiayaan?: number;
  laba_rugi?: number;
  biaya?: number;
  dpk?: number;
  fdr?: number;
  npf?: number;
  bopo?: number;
  cash_ratio?: number;
  kpmm?: number;
}

export interface DashboardData {
  funding: {
    total: number;
    growth: number;
    pencairan: {
      total: number;
      growth: number;
    };
  };
  lending: {
    total: number;
    growth: number;
  };
  npf: {
    ratio: number;
    amount: number;
    growth: number;
  };
  filterMonth: number;
  filterYear: number;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  error?: string;
}

export interface ChartData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string;
    borderColor?: string;
    borderWidth?: number;
  }[];
}

export interface FilterOptions {
  month?: number;
  year?: number;
  comparison?: 'MOM' | 'YOY';
  products?: string[];
}
