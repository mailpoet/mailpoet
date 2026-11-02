import type { Filter } from '@wordpress/dataviews';
import type { Category } from 'common/categories/categories-item';
import type { ListingGroup } from './types';

/**
 * Listing groups as tabs, with the classes and ids the tab bar has always had.
 * An empty Trash tab shows only while Trash is open.
 */
export function groupsToTabs(
  groups: ListingGroup[],
  group: string,
): Category[] {
  const visible = groups.filter(
    ({ name, count }) =>
      !(name === 'trash' && count === 0 && group !== 'trash'),
  );
  return visible.map(({ name, label, count }) => ({
    name,
    label,
    count,
    automationId: `filters_${name}`,
    className: `mailpoet-dataviews-group-${name}`,
  }));
}

/** The value of an “is any” filter on `field` that holds exactly one value. */
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
 * filter is exactly its status, and All when it isn’t a single tab status.
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

/**
 * The group and filters after picking a tab, or null when nothing changes.
 * A status tab sets the status filter, All clears it, Trash keeps every filter.
 */
export function getTabChange(
  tab: string,
  group: string,
  filters: Filter[] | undefined,
  field: string,
): { group: 'all' | 'trash'; filters: Filter[] } | null {
  const next =
    tab === 'trash'
      ? { group: 'trash' as const, filters: filters ?? [] }
      : {
          group: 'all' as const,
          filters: withStatusTab(filters, field, tab === 'all' ? null : tab),
        };
  const byField = (list: Filter[]) =>
    JSON.stringify([...list].sort((a, b) => a.field.localeCompare(b.field)));
  const unchanged =
    next.group === group && byField(next.filters) === byField(filters ?? []);
  return unchanged ? null : next;
}
