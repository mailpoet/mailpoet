import type { View } from '@wordpress/dataviews';

type FilterParam = Record<string, unknown>;

function isEmpty(value: unknown): boolean {
  if (value === undefined || value === null || value === '') return true;
  return Array.isArray(value) && value.length === 0;
}

/**
 * Translate a single date filter into the endpoint's `created_*` / `updated_*`
 * bounds. DataViews date filters carry an operator (`before` / `after` /
 * `between`) alongside the value; `between` stores a `[from, to]` tuple.
 */
function dateFilterParams(
  field: 'created_at' | 'updated_at',
  operator: string | undefined,
  value: unknown,
): FilterParam {
  const prefix = field === 'created_at' ? 'created' : 'updated';
  const params: FilterParam = {};

  if (operator === 'between' && Array.isArray(value)) {
    const [from, to] = value;
    if (!isEmpty(from)) params[`${prefix}_after`] = from;
    if (!isEmpty(to)) params[`${prefix}_before`] = to;
    return params;
  }
  if (operator === 'after' || operator === 'afterInc') {
    params[`${prefix}_after`] = value;
    return params;
  }
  if (operator === 'before' || operator === 'beforeInc') {
    params[`${prefix}_before`] = value;
  }
  return params;
}

/**
 * Serialize DataViews filter state into the `filter[field]=value` shape the
 * automations REST endpoint expects. Status is intentionally omitted — it is
 * driven by the listing tabs, not a native filter. Empty filters are dropped so
 * an inactive filter never reaches the request.
 */
export function filtersToParam(
  filters: View['filters'],
): Record<string, unknown> {
  return (filters ?? []).reduce<FilterParam>((result, filter) => {
    const { field, operator, value } = filter;
    if (isEmpty(value)) return result;

    if (field === 'created_at' || field === 'updated_at') {
      return { ...result, ...dateFilterParams(field, operator, value) };
    }

    return { ...result, [field]: value };
  }, {});
}
