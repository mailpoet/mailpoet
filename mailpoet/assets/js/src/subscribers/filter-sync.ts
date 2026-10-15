import { WITHOUT_LIST, type SubscribersRequestFilter } from './filters';

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

type KnownIds = { segments?: string[]; tags?: string[] };

const PRUNABLE_KEYS: Array<[keyof SubscribersRequestFilter, keyof KnownIds]> = [
  ['segment_in', 'segments'],
  ['segment_not_in', 'segments'],
  ['tag_in', 'tags'],
  ['tag_not_in', 'tags'],
];

// Drops list/tag ids that are no longer selectable, value by value, and returns
// the pruned filter or `null` when nothing changed. `without-list` is always
// kept, and a key is only pruned once its option list has loaded (non-empty).
export function pruneUnknownIds(
  filter: SubscribersRequestFilter,
  known: KnownIds,
): SubscribersRequestFilter | null {
  const next: SubscribersRequestFilter = { ...filter };
  let changed = false;
  PRUNABLE_KEYS.forEach(([key, source]) => {
    const values = filter[key] as Array<number | string> | undefined;
    const options = known[source];
    if (!values || !options || options.length === 0) {
      return;
    }
    const kept = values.filter(
      (value) => value === WITHOUT_LIST || options.includes(String(value)),
    );
    if (kept.length === values.length) {
      return;
    }
    changed = true;
    if (kept.length > 0) {
      (next as Record<string, unknown>)[key] = kept;
    } else {
      delete next[key];
    }
  });
  return changed ? next : null;
}
