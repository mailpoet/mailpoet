import { useCallback, useEffect, useState } from 'react';
import { api } from '../config';

const API_URL = `${api.root}/mailpoet/v1`;

export const request = (
  path: string,
  init?: RequestInit,
): ReturnType<typeof fetch> => fetch(`${API_URL}/${path}`, init);

type Error<T> = {
  response?: Response;
  data?: T;
};

type State<T> = {
  data?: T;
  loading: boolean;
  error?: Error<T>;
};

type Result<T> = [(init?: RequestInit) => Promise<void>, State<T>];

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Data = Record<string, any>;

export const useMutation = <T extends Data>(
  path: string,
  config?: RequestInit,
): Result<T> => {
  const [state, setState] = useState<State<T>>({
    data: undefined,
    loading: false,
    error: undefined,
  });

  const mutation = useCallback(
    async (init?: RequestInit) => {
      setState((prevState) => ({ ...prevState, loading: true }));
      const response = await request(path, {
        ...config,
        ...init,
        headers: {
          'content-type': 'application/json',
          ...(init?.headers ?? {}),
          'x-wp-nonce': api.nonce,
        },
      });

      try {
        const data = await response.json();
        const error = response.ok ? null : { ...response, data };
        setState((prevState) => ({ ...prevState, data, error }));
      } catch (_) {
        const error = { response };
        setState((prevState) => ({ ...prevState, error }));
      } finally {
        setState((prevState) => ({ ...prevState, loading: false }));
      }
    },
    [config, path],
  );

  return [mutation, state];
};

export const useQuery = <T extends Data>(
  path: string,
  init?: RequestInit,
): State<T> => {
  const [mutation, result] = useMutation<T>(path, init);

  useEffect(
    () => {
      void mutation();
    },
    [] /* eslint-disable-line react-hooks/exhaustive-deps -- request only on initial load */,
  );

  return result;
};
