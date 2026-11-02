import type { Filter } from '@wordpress/dataviews';

/** The value of an "is any" filter on `field` that holds exactly one value. */
export function getSingleFilterValue(
  filters: Filter[] | undefined,
  field: string,
): string | null {
  const filter = (filters ?? []).find(
    (entry) => entry.field === field && entry.operator === 'isAny',
  );
  const values = Array.isArray(filter?.value) ? filter.value : [];
  return values.length === 1 ? String(values[0]) : null;
}

/**
 * Status tabs are a shortcut for the status filter: a tab is active when the
 * filter is exactly its status, and All when it isn't a single tab status.
 */
export function getActiveStatusTab(
  group: string,
  filters: Filter[] | undefined,
  field: string,
  statuses: string[],
): string {
  if (group === 'trash') {
    return group;
  }
  const status = getSingleFilterValue(filters, field);
  return status && statuses.includes(status) ? status : 'all';
}

/** The filters after picking a status tab, or All when `status` is null. */
export function withStatusTab(
  filters: Filter[] | undefined,
  field: string,
  status: string | null,
): Filter[] {
  const others = (filters ?? []).filter((entry) => entry.field !== field);
  return status
    ? [...others, { field, operator: 'isAny', value: [status] }]
    : others;
}
