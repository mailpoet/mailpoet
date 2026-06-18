import {
  useCallback,
  useEffect,
  useRef,
  useState,
  type Dispatch,
  type SetStateAction,
} from 'react';
import type { View } from '@wordpress/dataviews';
import type {
  ListingFilters,
  ListingMeta,
  ListingQueryParams,
  ListingResponse,
} from './types';

export type LoadListing<T> = (
  params: ListingQueryParams,
  signal?: AbortSignal,
) => Promise<ListingResponse<T>>;

type UseDataViewsQueryOptions<T> = {
  initialView: View;
  load: LoadListing<T>;
  /**
   * Read a listing-specific param (e.g. group) off the current view.
   * Return undefined to omit the param from the request.
   */
  extraParams?: (view: View) => Partial<ListingQueryParams>;
};

type UseDataViewsQueryResult<T> = {
  view: View;
  setView: Dispatch<SetStateAction<View>>;
  onChangeView: (nextView: View) => void;
  items: T[];
  meta: ListingMeta;
  filters: ListingFilters;
  groups: ListingResponse<T>['groups'];
  isLoading: boolean;
  error: string | null;
  refresh: () => void;
  clearError: () => void;
};

const EMPTY_META: ListingMeta = { count: 0, pages: 0 };
const EMPTY_FILTERS: ListingFilters = {};

function wasInitialUrlStateReset(currentView: View, nextView: View): boolean {
  const pageReset = (currentView.page ?? 1) > 1 && (nextView.page ?? 1) === 1;
  const searchReset = Boolean(currentView.search) && !nextView.search;

  return pageReset || searchReset;
}

/**
 * Bind a `@wordpress/dataviews` view to a MailPoet REST listing endpoint.
 *
 * Handles mapping `view` state to REST query params, race-guarded fetch
 * (later requests win, out-of-order responses are dropped), and clamps
 * the current page when the dataset shrinks below the requested page.
 */
export function useDataViewsQuery<T>({
  initialView,
  load,
  extraParams,
}: UseDataViewsQueryOptions<T>): UseDataViewsQueryResult<T> {
  const [view, setView] = useState<View>(initialView);
  const [items, setItems] = useState<T[]>([]);
  const [meta, setMeta] = useState<ListingMeta>(EMPTY_META);
  const [filters, setFilters] = useState<ListingFilters>(EMPTY_FILTERS);
  const [groups, setGroups] = useState<ListingResponse<T>['groups']>(undefined);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [refreshToken, setRefreshToken] = useState(0);
  const latestRequestIdRef = useRef(0);
  const completedInitialRequestRef = useRef(false);
  // Stash `extraParams` in a ref so a non-memoized callback from the caller
  // doesn't retrigger the fetch effect on every render. Reading from the ref
  // also lets us keep `extraParams` out of the effect's dependency array
  // without losing the latest closure.
  const extraParamsRef = useRef(extraParams);
  extraParamsRef.current = extraParams;

  const refresh = useCallback(() => {
    setRefreshToken((token) => token + 1);
  }, []);

  const clearError = useCallback(() => setError(null), []);

  const onChangeView = useCallback((nextView: View): void => {
    setView((currentView) => {
      if (
        !completedInitialRequestRef.current &&
        wasInitialUrlStateReset(currentView, nextView)
      ) {
        return currentView;
      }

      const searchChanged =
        (nextView.search ?? '') !== (currentView.search ?? '');
      const perPageChanged = nextView.perPage !== currentView.perPage;
      const filtersChanged =
        JSON.stringify(currentView.filters ?? []) !==
        JSON.stringify(nextView.filters ?? []);

      return {
        ...nextView,
        page:
          searchChanged || perPageChanged || filtersChanged ? 1 : nextView.page,
      };
    });
  }, []);

  const queryPage = view.page ?? 1;
  const queryPerPage = view.perPage ?? 20;
  const queryOrderBy = view.sort?.field;
  const queryOrder = view.sort?.direction;
  const querySearch = view.search || undefined;
  const extraQueryParams = extraParamsRef.current
    ? extraParamsRef.current(view)
    : {};
  const extraQueryParamsKey = JSON.stringify(extraQueryParams);

  useEffect(() => {
    const controller = new AbortController();
    const requestId = latestRequestIdRef.current + 1;
    latestRequestIdRef.current = requestId;
    setIsLoading(true);

    const requestedPage = queryPage;
    const params: ListingQueryParams = {
      page: requestedPage,
      per_page: queryPerPage,
      orderby: queryOrderBy,
      order: queryOrder,
      search: querySearch,
      ...extraQueryParams,
    };

    load(params, controller.signal)
      .then((result) => {
        if (requestId !== latestRequestIdRef.current) {
          return;
        }
        const lastValidPage = Math.max(1, result.meta.pages);
        if (result.meta.pages > 0 && requestedPage > lastValidPage) {
          // Avoid leaving a stale error visible while we re-fetch the
          // clamped page below.
          setError(null);
          setView((currentView) => ({ ...currentView, page: lastValidPage }));
          return;
        }
        setItems(result.items);
        setMeta(result.meta);
        setFilters(result.filters ?? EMPTY_FILTERS);
        setGroups(result.groups);
        setError(null);
      })
      .catch((err: unknown) => {
        if (requestId !== latestRequestIdRef.current) {
          return;
        }
        if (controller.signal.aborted) {
          return;
        }
        const message =
          err && typeof err === 'object' && 'message' in err
            ? String((err as { message?: unknown }).message ?? '')
            : '';
        setItems([]);
        setMeta({ count: 0, pages: 0 });
        setFilters(EMPTY_FILTERS);
        setGroups([]);
        setError(message || 'Failed to load data.');
      })
      .finally(() => {
        if (requestId === latestRequestIdRef.current) {
          completedInitialRequestRef.current = true;
          setIsLoading(false);
        }
      });
    return () => controller.abort();
    // `extraParams` is read via a ref above so the effect doesn't depend on
    // its identity (callers don't need to memoize it). The effect re-runs when
    // query params change, including listing-specific params derived from the
    // full DataViews view.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [
    queryPage,
    queryPerPage,
    queryOrderBy,
    queryOrder,
    querySearch,
    extraQueryParamsKey,
    load,
    refreshToken,
  ]);

  return {
    view,
    setView,
    onChangeView,
    items,
    meta,
    filters,
    groups,
    isLoading,
    error,
    refresh,
    clearError,
  };
}
