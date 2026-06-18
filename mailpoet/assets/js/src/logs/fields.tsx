import { createElement } from 'react';
import { __ } from '@wordpress/i18n';
import type { Action, Field } from '@wordpress/dataviews';
import { MailPoetDate } from '../date';
import type { LogListingItem } from './api';
import {
  getLogFilterOptions,
  getLogSeverityElements,
  getLogSeverityLabel,
  type LogFilterOptions,
} from './filters';

export function formatCreatedAt(createdAt: string | null): string {
  return createdAt ? MailPoetDate.full(createdAt) : '—';
}

export function getLogFields(
  expandedLogIds: Set<number>,
  options: LogFilterOptions = getLogFilterOptions(),
): Field<LogListingItem>[] {
  return [
    {
      id: 'name',
      label: __('Name', 'mailpoet'),
      type: 'text',
      enableSorting: true,
      enableGlobalSearch: false,
      elements: options.names.map((name) => ({ value: name, label: name })),
      filterBy: { operators: ['isAny'] },
      render: ({ item }) =>
        createElement(
          'span',
          { className: 'mailpoet-logs-min-width' },
          item.name,
        ),
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
        createElement(
          'span',
          null,
          item.level === null ? '—' : getLogSeverityLabel(item.level),
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
        return createElement(
          'div',
          {
            id: `mailpoet-log-message-${item.id}`,
            className: `mailpoet-logs-message ${
              isExpanded ? 'mailpoet-logs-message-full' : ''
            }`,
          },
          item.message,
        );
      },
    },
    {
      id: 'created_at',
      label: __('Created On', 'mailpoet'),
      type: 'date',
      enableSorting: true,
      enableGlobalSearch: false,
      filterBy: { operators: ['on', 'before', 'after', 'between'] },
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
export function getLogFieldDefinitions(
  options: LogFilterOptions = getLogFilterOptions(),
): Field<LogListingItem>[] {
  return getLogFields(EMPTY_EXPANDED_LOG_IDS, options);
}
