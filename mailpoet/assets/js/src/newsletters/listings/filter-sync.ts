import type { NewslettersRequestFilter } from './filters';

// The one-time prune needs a response that carries the list options, so it
// runs on the first applied response after it was armed (`armedAfter` is the
// response version at that moment, or Infinity once done).
export function shouldPruneAfterResponse(
  responseVersion: number,
  armedAfter: number,
): boolean {
  return responseVersion > armedAfter;
}

// Drops list ids that are no longer selectable (for example a deleted list)
// and returns the pruned filter, or `null` when nothing changed. Nothing is
// pruned while the option list is missing; a loaded empty list means no list
// exists, so every list id is dropped.
export function pruneUnknownIds(
  filter: NewslettersRequestFilter,
  known: { segments?: string[] },
): NewslettersRequestFilter | null {
  const ids = filter.segment_ids;
  const options = known.segments;
  if (!ids || !options) return null;
  const kept = ids.filter((id) => options.includes(id));
  if (kept.length === ids.length) return null;
  if (kept.length > 0) return { ...filter, segment_ids: kept };
  const next = { ...filter };
  delete next.segment_ids;
  delete next.segment_operator;
  return next;
}
