// resources/js/hooks/useApi.ts
import { useState, useCallback } from 'react';
import axios, { AxiosResponse } from 'axios';
import { ApiResponse } from '@/types';

interface UseApiState<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
}

export function useApi<T>() {
  const [state, setState] = useState<UseApiState<T>>({
    data: null,
    loading: false,
    error: null,
  });

  const execute = useCallback(async (url: string, options?: any): Promise<T | null> => {
    setState({ data: null, loading: true, error: null });

    try {
      const response: AxiosResponse<ApiResponse<T>> = await axios(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
        ...options,
      });

      if (response.data.error) {
        throw new Error(response.data.error);
      }

      setState({ data: response.data.data, loading: false, error: null });
      return response.data.data;
    } catch (error: any) {
      const errorMessage = error.response?.data?.message || error.message || 'An error occurred';
      setState({ data: null, loading: false, error: errorMessage });
      return null;
    }
  }, []);

  const reset = useCallback(() => {
    setState({ data: null, loading: false, error: null });
  }, []);

  return { ...state, execute, reset };
}
