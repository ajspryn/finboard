// resources/js/utils/formatters.ts
export const formatCurrency = (amount: number): string => {
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

export const formatPercentage = (value: number, decimals: number = 2): string => {
  return `${value.toFixed(decimals)}%`;
};

export const formatNumber = (value: number): string => {
  return value.toLocaleString();
};

export const getGrowthColor = (growth: number): string => {
  if (growth > 0) return 'success';
  if (growth < 0) return 'danger';
  return 'warning';
};

export const getGrowthIcon = (growth: number): string => {
  return growth >= 0 ? 'ti-trending-up' : 'ti-trending-down';
};
