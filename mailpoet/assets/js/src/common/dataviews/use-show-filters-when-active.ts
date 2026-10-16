import { useEffect, type RefObject } from 'react';

// DataViews hides the filter row until the toggle is pressed (its state starts
// as `false`, dataviews/index.mjs:160) and has no prop for the initial value;
// `DataViewsContext` is not exported either. The only stock way to open it is
// the toggle itself, so find that button.
const COLLAPSED_FILTERS_TOGGLE =
  '.dataviews-filters__visibility-toggle[aria-expanded="false"]';

/**
 * Keeps active filters visible: presses the DataViews filter toggle once when
 * the first filter appears (URL, Back/Forward or the Add filter menu). It does
 * not reopen the row while filters stay set, so users can still hide it.
 * Pair with `<DataViews.FiltersToggled />` and pass the listing wrapper ref.
 */
export function useShowFiltersWhenActive(
  wrapperRef: RefObject<HTMLElement>,
  hasFilters: boolean,
): void {
  useEffect(() => {
    if (!hasFilters) return;
    wrapperRef.current
      ?.querySelector<HTMLButtonElement>(COLLAPSED_FILTERS_TOGGLE)
      ?.click();
  }, [hasFilters, wrapperRef]);
}
