import { WITHOUT_LIST, type SubscribersRequestFilter } from './filters';

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
