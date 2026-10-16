import { useEffect, type RefObject } from 'react';

// DataViews hides the filter row until the toggle is pressed (its state starts
// as `false`, dataviews/index.mjs:160) and has no prop for the initial value;
// `DataViewsContext` is not exported either. The only stock way to open it is
// the toggle itself, so find that button. Until a filter is registered (for
// example options that load with the response) the same button, with the same
// class, is the "Add filter" menu trigger; only that one has aria-haspopup.
const FILTERS_TOGGLE =
  '.dataviews-filters__visibility-toggle:not([aria-haspopup])';

/**
 * Keeps active filters visible: presses the DataViews filter toggle once when
 * the first filter appears (URL, Back/Forward or the Add filter menu). It does
 * not reopen the row while filters stay set, so users can still hide it. If the
 * toggle is not there yet, it waits for it to appear.
 * Pair with `<DataViews.FiltersToggled />` and pass the listing wrapper ref.
 */
export function useShowFiltersWhenActive(
  wrapperRef: RefObject<HTMLElement>,
  hasFilters: boolean,
): void {
  useEffect(() => {
    const wrapper = wrapperRef.current;
    if (!hasFilters || !wrapper) return undefined;
    const press = (): boolean => {
      const toggle = wrapper.querySelector<HTMLButtonElement>(FILTERS_TOGGLE);
      if (toggle?.getAttribute('aria-expanded') === 'false') {
        toggle.click();
      }
      return !!toggle;
    };
    if (press()) return undefined;
    const observer = new MutationObserver(() => {
      if (press()) observer.disconnect();
    });
    observer.observe(wrapper, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['aria-haspopup'],
    });
    return () => observer.disconnect();
  }, [hasFilters, wrapperRef]);
}
