import { __ } from '@wordpress/i18n';
import type { Field, Filter } from '@wordpress/dataviews';
import type { ListingFilterOption } from 'common/dataviews/types';
import type { NewsletterListingItem } from '../api';
import { SEGMENTS_FIELD, SENT_AT_FIELD, STATUS_FIELD } from './filters';

type Element = { value: string; label: string };

const FILTER_FIELDS = [STATUS_FIELD, SEGMENTS_FIELD, SENT_AT_FIELD];

export const NEWSLETTER_STATUS_ELEMENTS: Record<string, Element> = {
  draft: { value: 'draft', label: __('Draft', 'mailpoet') },
  scheduled: { value: 'scheduled', label: __('Scheduled', 'mailpoet') },
  sending: { value: 'sending', label: __('Sending', 'mailpoet') },
  paused: { value: 'paused', label: __('Paused', 'mailpoet') },
  sent: { value: 'sent', label: __('Sent', 'mailpoet') },
};

export const ACTIVATION_STATUS_ELEMENTS: Element[] = [
  { value: 'active', label: __('Active', 'mailpoet') },
  { value: 'draft', label: __('Not active', 'mailpoet') },
];

export const SEGMENT_OPERATORS = ['isAny', 'isNone'] as const;

export const SENT_AT_OPERATORS = [
  'before',
  'after',
  'between',
  'beforeInc',
  'afterInc',
] as const;

export function statusElements(values: string[]): Element[] {
  return values.map((value) => NEWSLETTER_STATUS_ELEMENTS[value]);
}

// Paused is a queue state of a sending email, not a status of its own.
export function getStatusFilterValue(item: NewsletterListingItem): string {
  return item.status === 'sending' &&
    item.queue &&
    item.queue.status === 'paused'
    ? 'paused'
    : item.status;
}

export function getSegmentFilterIds(filters: Filter[] | undefined): string[] {
  const filter = (filters ?? []).find((item) => item.field === SEGMENTS_FIELD);
  return (Array.isArray(filter?.value) ? filter.value : []).map((id) =>
    String(id),
  );
}

// Selected ids missing from the options (a deleted list in a bookmark) stay
// selectable and removable, labelled by their id.
export function buildSegmentElements(
  options: ListingFilterOption[] | undefined,
  selectedIds: string[],
): Element[] {
  const elements = (options ?? [])
    .filter((option) => String(option.value) !== '')
    .map((option) => ({
      value: String(option.value),
      label: option.label,
    }));
  selectedIds.forEach((id) => {
    if (!elements.some((element) => element.value === id)) {
      elements.push({ value: id, label: id });
    }
  });
  return elements;
}

// DataViews makes every field filterable by default; only the fields that map
// to a request filter should be.
export function applyFilterConfig(
  fields: Field<NewsletterListingItem>[],
  config: { segmentElements: Element[]; statusEnabled: boolean },
): Field<NewsletterListingItem>[] {
  return fields.map((field) => {
    if (!FILTER_FIELDS.includes(field.id)) return { ...field, filterBy: false };
    if (field.id === SEGMENTS_FIELD) {
      return config.segmentElements.length > 0
        ? { ...field, elements: config.segmentElements }
        : { ...field, filterBy: false };
    }
    if (field.id === STATUS_FIELD && !config.statusEnabled) {
      return { ...field, filterBy: false };
    }
    return field;
  });
}

// Filter-only field: the activation listings show lists inside "Settings".
export const activationSegmentsField: Field<NewsletterListingItem> = {
  id: SEGMENTS_FIELD,
  label: __('Lists', 'mailpoet'),
  type: 'text',
  enableSorting: false,
  enableGlobalSearch: false,
  enableHiding: false,
  getValue: ({ item }) => item.segments.map((segment) => String(segment.id)),
  filterBy: { operators: [...SEGMENT_OPERATORS] },
};
