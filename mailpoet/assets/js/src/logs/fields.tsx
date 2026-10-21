import { createElement, type ReactNode } from 'react';
import { __ } from '@wordpress/i18n';
import type { Action, Field } from '@wordpress/dataviews';
import { MailPoetDate } from '../date';
import { Tag } from '../common/tag/tag';
import { getLogNames, type LogListingItem } from './api';
import {
  getLogSeverityDescription,
  getLogSeverityElements,
  getLogSeverityLabel,
  getLogSeverityVariant,
  getLogTopicElements,
  getLogTopicLabel,
} from './filters';

// One stable reference so DataViews does not reload the options on every render.
const loadTopicElements = () => getLogNames().then(getLogTopicElements);

export function formatCreatedAt(createdAt: string | null): string {
  return createdAt ? MailPoetDate.full(createdAt) : '—';
}

function isScalar(value: unknown): value is string | number | boolean {
  return (
    typeof value === 'string' ||
    typeof value === 'number' ||
    typeof value === 'boolean'
  );
}

// The plain message (placeholders filled from the context) shown in the
// column. When the search matched only the formatted line, show that line so
// the visible text explains the match.
export function getLogDisplayMessage(
  item: LogListingItem,
  searchTerm?: string,
): string {
  let text = item.raw_message?.trim() ? item.raw_message : item.message;
  Object.entries(item.context ?? {}).forEach(([key, value]) => {
    if (isScalar(value)) {
      text = text.split(`{${key}}`).join(String(value));
    }
  });
  const term = searchTerm?.trim().toLowerCase();
  if (
    term &&
    !text.toLowerCase().includes(term) &&
    !item.name.toLowerCase().includes(term)
  ) {
    return item.message;
  }
  return text;
}

// Labels in the expanded details are plain English on purpose (logs are not
// translated).
function renderLogDetails(item: LogListingItem): ReactNode {
  const topicLabel = getLogTopicLabel(item.name);
  const rows: { id: string; term: string; description: ReactNode }[] = [];
  if (item.level !== null) {
    const description = getLogSeverityDescription(item.level);
    const label = getLogSeverityLabel(item.level);
    rows.push({
      id: 'log:level',
      term: 'Severity',
      description: description ? `${label} – ${description}` : label,
    });
  }
  rows.push({
    id: 'log:name',
    term: 'Topic',
    description:
      topicLabel === item.name ? item.name : `${topicLabel} (${item.name})`,
  });
  Object.entries(item.context ?? {}).forEach(([key, value]) => {
    rows.push({
      id: `context:${key}`,
      term: key,
      description: isScalar(value)
        ? String(value)
        : createElement('pre', null, JSON.stringify(value, null, 2)),
    });
  });
  const showFullLine =
    item.raw_message !== null && item.raw_message !== item.message;
  return createElement(
    'div',
    { className: 'mailpoet-logs-details' },
    createElement(
      'dl',
      null,
      rows.map((row) => [
        createElement('dt', { key: `term-${row.id}` }, row.term),
        createElement('dd', { key: `description-${row.id}` }, row.description),
      ]),
    ),
    showFullLine
      ? createElement(
          'div',
          null,
          createElement('strong', null, __('Full log line', 'mailpoet')),
          createElement('pre', null, item.message),
        )
      : null,
  );
}

export function getLogFields(
  expandedLogIds: Set<number>,
  searchTerm?: string,
): Field<LogListingItem>[] {
  return [
    {
      id: 'name',
      label: __('Name', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: false,
      enableHiding: false,
      getElements: loadTopicElements,
      filterBy: { operators: ['isAny'] },
      render: ({ item }) => {
        const label = getLogTopicLabel(item.name);
        return createElement(
          'span',
          { className: 'mailpoet-logs-topic', title: item.name },
          label,
          label !== item.name
            ? createElement(
                'span',
                { className: 'mailpoet-logs-topic-raw' },
                item.name,
              )
            : null,
        );
      },
    },
    {
      // Rendered as a textual severity label, so use the text type to keep the
      // column left-aligned; the integer type would right-align header + cells.
      id: 'level',
      label: __('Severity', 'mailpoet'),
      type: 'text',
      enableSorting: false,
      enableGlobalSearch: false,
      elements: getLogSeverityElements(),
      filterBy: { operators: ['isAny'] },
      render: ({ item }) =>
        item.level === null
          ? createElement('span', null, '—')
          : createElement(
              Tag,
              {
                variant: getLogSeverityVariant(item.level),
                title: getLogSeverityDescription(item.level),
              },
              getLogSeverityLabel(item.level),
            ),
    },
    {
      id: 'message',
      label: __('Message', 'mailpoet'),
      type: 'text',
      enableSorting: false,
      enableGlobalSearch: false,
      filterBy: false,
      render: ({ item }) => {
        const isExpanded = expandedLogIds.has(item.id);
        const message = createElement(
          'pre',
          {
            id: `mailpoet-log-message-${item.id}`,
            className: `mailpoet-logs-message ${
              isExpanded ? 'mailpoet-logs-message-full' : ''
            }`,
          },
          getLogDisplayMessage(item, searchTerm),
        );
        return isExpanded
          ? createElement('div', null, message, renderLogDetails(item))
          : message;
      },
    },
    {
      id: 'full_message',
      label: __('Full log line', 'mailpoet'),
      type: 'text',
      enableSorting: false,
      enableGlobalSearch: false,
      enableHiding: true,
      filterBy: false,
      getValue: ({ item }) => item.message,
      render: ({ item }) =>
        createElement(
          'pre',
          { className: 'mailpoet-logs-message' },
          item.message,
        ),
    },
    {
      id: 'created_at',
      label: __('Created On', 'mailpoet'),
      type: 'date',
      enableSorting: true,
      enableGlobalSearch: false,
      filterBy: { operators: ['on', 'beforeInc', 'afterInc', 'between'] },
      render: ({ item }) =>
        createElement(
          'span',
          { className: 'mailpoet-logs-min-width' },
          formatCreatedAt(item.created_at),
        ),
    },
  ];
}

// Native primary row action that toggles the truncated message open/closed.
// The label reflects the row's current expanded state.
export function getLogActions(
  expandedLogIds: Set<number>,
  onToggleExpanded: (logId: number) => void,
): Action<LogListingItem>[] {
  return [
    {
      id: 'toggle-message',
      isPrimary: true,
      label: (items) =>
        items[0] && expandedLogIds.has(items[0].id)
          ? __('Show less', 'mailpoet')
          : __('Show more', 'mailpoet'),
      callback: (items) => {
        const item = items[0];
        if (item) {
          onToggleExpanded(item.id);
        }
      },
    },
  ];
}

const EMPTY_EXPANDED_LOG_IDS = new Set<number>();

// Stable field definitions for callers that only need the field schema (e.g.
// DataViews preference validation), without per-row expand/collapse state.
export function getLogFieldDefinitions(): Field<LogListingItem>[] {
  return getLogFields(EMPTY_EXPANDED_LOG_IDS);
}
