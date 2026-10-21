import { __, sprintf } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import type { ListingFilterOption } from 'common/dataviews/types';
import type { NewsletterListingItem } from '../api';
import {
  getAllowedStatuses,
  SEGMENTS_FIELD,
  SENT_AT_FIELD,
  STATUS_FIELD,
} from './filters';

type Element = { value: string; label: string };

export function formatLabelWithCount(label: string, count: number): string {
  return sprintf(
    /* translators: %1$s is the name of a filter option, %2$s is the number of matching items. */
    __('%1$s (%2$s)', 'mailpoet'),
    label,
    count.toLocaleString(),
  );
}

const FILTER_FIELDS = [STATUS_FIELD, SEGMENTS_FIELD, SENT_AT_FIELD];

export const NEWSLETTER_STATUS_ELEMENTS: Record<string, Element> = {
  draft: { value: 'draft', label: __('Draft', 'mailpoet') },
  scheduled: { value: 'scheduled', label: __('Scheduled', 'mailpoet') },
  sending: { value: 'sending', label: __('Sending', 'mailpoet') },
  paused: { value: 'paused', label: __('Paused', 'mailpoet') },
  sent: { value: 'sent', label: __('Sent', 'mailpoet') },
  active: { value: 'active', label: __('Active', 'mailpoet') },
};

export const SEGMENT_OPERATORS = ['isAny', 'isNone'] as const;

// Every operator survives a round-trip through the sent_from / sent_to hash
// keys. DataViews starts a new filter on the first one, and `between` is the
// one whose presets (Past week, Past month) select a range.
export const SENT_AT_OPERATORS = ['between', 'on', 'before', 'after'] as const;

export function statusElements(
  values: string[],
  labels: Record<string, string> = {},
): Element[] {
  return values.map((value) => ({
    ...NEWSLETTER_STATUS_ELEMENTS[value],
    ...(labels[value] ? { label: labels[value] } : {}),
  }));
}

export const ACTIVATION_STATUS_ELEMENTS: Element[] = statusElements(
  getAllowedStatuses('notification'),
  { draft: __('Not active', 'mailpoet') },
);

// Paused is a queue state of a sending email, not a status of its own.
export function getStatusFilterValue(item: NewsletterListingItem): string {
  return item.status === 'sending' &&
    item.queue &&
    item.queue.status === 'paused'
    ? 'paused'
    : item.status;
}

export function buildSegmentElements(
  options: ListingFilterOption[] | undefined,
): Element[] {
  return (options ?? [])
    .filter((option) => String(option.value) !== '')
    .map((option) => ({
      value: String(option.value),
      label: option.label,
    }));
}

// DataViews makes every field filterable by default; only the fields that map
// to a request filter should be.
export function applyFilterConfig(
  fields: Field<NewsletterListingItem>[],
  config: { segmentElements: Element[]; statusLabels: Record<string, string> },
): Field<NewsletterListingItem>[] {
  return fields.map((field) => {
    if (!FILTER_FIELDS.includes(field.id)) return { ...field, filterBy: false };
    if (field.id === SEGMENTS_FIELD) {
      return config.segmentElements.length > 0
        ? { ...field, elements: config.segmentElements }
        : { ...field, filterBy: false };
    }
    if (field.id === STATUS_FIELD && field.elements) {
      return {
        ...field,
        elements: field.elements.map((element) => ({
          ...element,
          label: config.statusLabels[element.value] ?? element.label,
        })),
      };
    }
    return field;
  });
}

export const segmentsFieldBase: Pick<
  Field<NewsletterListingItem>,
  | 'id'
  | 'label'
  | 'type'
  | 'enableSorting'
  | 'enableGlobalSearch'
  | 'getValue'
  | 'filterBy'
> = {
  id: SEGMENTS_FIELD,
  label: __('Lists', 'mailpoet'),
  type: 'text',
  enableSorting: false,
  enableGlobalSearch: false,
  getValue: ({ item }) => item.segments.map((segment) => String(segment.id)),
  filterBy: { operators: [...SEGMENT_OPERATORS] },
};

// Filter-only field: the activation listings show lists inside "Settings".
export const activationSegmentsField: Field<NewsletterListingItem> = {
  ...segmentsFieldBase,
  enableHiding: false,
};
