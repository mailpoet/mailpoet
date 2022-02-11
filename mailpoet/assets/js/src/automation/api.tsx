import { useCallback, useEffect, useState } from 'react';

const API_URL = '/wp-json/mailpoet/v1/automation';

export const request = (path: string, init?: RequestInit): ReturnType<typeof fetch> => (
  fetch(`${API_URL}/${path}`, init)
);

type Error<T> = {
  response?: Response;
  data?: T;
};

type State<T> = {
  data?: T;
  loading: boolean;
  error?: Error<T>;
};

type Result<T> = [
  () => Promise<void>,
  State<T>,
];

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type Data = Record<string, any>;

export const useMutation = <T extends Data>(path: string, init?: RequestInit): Result<T> => {
  const [state, setState] = useState<State<T>>({
    data: undefined,
    loading: false,
    error: undefined,
  });

  const mutation = useCallback(async () => {
    setState((prevState) => ({ ...prevState, loading: true }));
    const response = await request(path, init);
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
  }, [init, path]);

  return [mutation, state];
};

export const useQuery = <T extends Data>(path: string, init?: RequestInit): State<T> => {
  const [mutation, result] = useMutation<T>(path, init);

  useEffect(
    () => {
      void mutation();
    },
    [], /* eslint-disable-line react-hooks/exhaustive-deps -- request only on initial load */
  );

  return result;
};
