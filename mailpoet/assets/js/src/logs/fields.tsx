import { createElement } from 'react';
import { __, sprintf } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
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

type LogExpansionButtonProps = {
  item: LogListingItem;
  isExpanded: boolean;
  onToggle: (logId: number) => void;
};

function LogExpansionButton({
  item,
  isExpanded,
  onToggle,
}: LogExpansionButtonProps): JSX.Element {
  const messageId = `mailpoet-log-message-${item.id}`;

  return (
    <button
      type="button"
      className="button button-secondary button-small mailpoet-button"
      aria-expanded={isExpanded}
      aria-controls={messageId}
      aria-label={
        isExpanded
          ? sprintf(
              // translators: %d is a log entry ID.
              __('Show less of log message %d', 'mailpoet'),
              item.id,
            )
          : sprintf(
              // translators: %d is a log entry ID.
              __('Show more of log message %d', 'mailpoet'),
              item.id,
            )
      }
      onClick={() => onToggle(item.id)}
    >
      <span>
        {isExpanded ? __('Show less', 'mailpoet') : __('Show more', 'mailpoet')}
      </span>
    </button>
  );
}

export function getLogFields(
  expandedLogIds: Set<number>,
  onToggleExpanded: (logId: number) => void,
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
      id: 'action',
      label: __('Action', 'mailpoet'),
      enableSorting: false,
      enableGlobalSearch: false,
      filterBy: false,
      render: ({ item }) =>
        createElement(LogExpansionButton, {
          item,
          isExpanded: expandedLogIds.has(item.id),
          onToggle: onToggleExpanded,
        }),
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

const EMPTY_EXPANDED_LOG_IDS = new Set<number>();
const NOOP_TOGGLE = (): void => undefined;

// Stable field definitions for callers that only need the field schema (e.g.
// DataViews preference validation), without per-row expand/collapse state.
export function getLogFieldDefinitions(
  options: LogFilterOptions = getLogFilterOptions(),
): Field<LogListingItem>[] {
  return getLogFields(EMPTY_EXPANDED_LOG_IDS, NOOP_TOGGLE, options);
}
