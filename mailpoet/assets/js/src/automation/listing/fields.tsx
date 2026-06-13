import { __, _x } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from '../../mailpoet';
import { AutomationStatus as AutomationStatusBadge } from '../components/status';
import { Statistics } from '../components/statistics';
import { triggerOptions } from '../config';
import type { AutomationItem } from './store/types';
import { getAutomationEditorUrl } from './urls';

function dateTime(value?: string): JSX.Element {
  if (!value) return <span>—</span>;
  return (
    <span>
      <span>{MailPoet.Date.short(value)}</span>
      <br />
      <span>{MailPoet.Date.time(value)}</span>
    </span>
  );
}

export const automationFields: Field<AutomationItem>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: true,
    enableSorting: true,
    filterBy: false,
    render: ({ item }) => (
      <a href={getAutomationEditorUrl(item)}>{item.name}</a>
    ),
  },
  {
    id: 'description',
    label: __('Description', 'mailpoet'),
    enableGlobalSearch: false,
    enableSorting: false,
    enableHiding: false,
    filterBy: false,
    render: ({ item }) =>
      item.description ? <div>{item.description}</div> : null,
  },
  {
    id: 'subscribers',
    label: __('Subscribers', 'mailpoet'),
    enableGlobalSearch: false,
    enableSorting: true,
    filterBy: false,
    getValue: ({ item }) => item.stats.totals.entered,
    render: ({ item }) => (
      <Statistics
        labelPosition="after"
        items={[
          {
            key: 'entered',
            // translators: Total number of subscribers who entered an automation
            label: _x('Entered', 'automation stats', 'mailpoet'),
            value: item.stats.totals.entered,
          },
          {
            key: 'processing',
            // translators: Total number of subscribers who are being processed in an automation
            label: _x('Processing', 'automation stats', 'mailpoet'),
            value: item.stats.totals.in_progress,
          },
          {
            key: 'exited',
            // translators: Total number of subscribers who exited an automation, no matter the result
            label: _x('Exited', 'automation stats', 'mailpoet'),
            value: item.stats.totals.exited,
          },
        ]}
      />
    ),
  },
  {
    id: 'status',
    label: __('Status', 'mailpoet'),
    enableGlobalSearch: false,
    enableSorting: true,
    filterBy: false,
    getValue: ({ item }) => item.status,
    render: ({ item }) => (
      <span className="mailpoet-automation-listing-cell-status">
        <AutomationStatusBadge status={item.status} />
      </span>
    ),
  },
  {
    id: 'trigger',
    label: __('Trigger', 'mailpoet'),
    enableGlobalSearch: false,
    enableSorting: false,
    elements: triggerOptions,
    filterBy: { operators: ['isAny', 'isNone'] },
    getValue: () => '',
    render: () => null,
  },
  {
    id: 'activity',
    label: __('Activity', 'mailpoet'),
    enableGlobalSearch: false,
    enableSorting: false,
    elements: [
      { value: 'has', label: __('Has subscribers entered', 'mailpoet') },
      { value: 'none', label: __('No subscribers entered', 'mailpoet') },
    ],
    filterBy: { operators: ['is'] },
    getValue: () => '',
    render: () => null,
  },
  {
    id: 'created_at',
    label: __('Created on', 'mailpoet'),
    type: 'datetime',
    enableGlobalSearch: false,
    enableSorting: true,
    filterBy: { operators: ['before', 'after', 'between'] },
    render: ({ item }) => dateTime(item.created_at),
  },
  {
    id: 'updated_at',
    label: __('Modified on', 'mailpoet'),
    type: 'datetime',
    enableGlobalSearch: false,
    enableSorting: true,
    filterBy: { operators: ['before', 'after', 'between'] },
    render: ({ item }) => dateTime(item.updated_at),
  },
];
