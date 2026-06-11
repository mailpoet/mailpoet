type FilterOptions =
  | Record<string, { value: string | number }[]>
  | undefined
  | null;

// Returns a copy of `filter` with any value that is no longer a selectable
// option removed, or `null` when nothing changed. A filter is only pruned once
// its options have loaded (a non-empty option list), so a valid filter is never
// cleared before the first listing response arrives.
//
// This keeps the filter dropdowns in sync with the query: the backend drops a
// list/tag from the options once it has no subscribers in the current group
// (e.g. after select-all + Move to trash empties the filtered list), which would
// otherwise leave the dropdown showing "All Lists" while the query stays
// filtered — "No items found" with a stale value.
export function pruneUnavailableFilters(
  filter: Record<string, string>,
  filters: FilterOptions,
): Record<string, string> | null {
  if (!filters) {
    return null;
  }
  const next = { ...filter };
  let changed = false;
  Object.entries(filter).forEach(([name, value]) => {
    if (!value) {
      return;
    }
    const options = filters[name];
    if (!Array.isArray(options) || options.length === 0) {
      return;
    }
    const isSelectable = options.some(
      (option) => String(option.value) === String(value),
    );
    if (!isSelectable) {
      delete next[name];
      changed = true;
    }
  });
  return changed ? next : null;
}
