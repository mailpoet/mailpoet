import { __, _x } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { AutomationStatus as AutomationStatusBadge } from '../components/status';
import { Statistics } from '../components/statistics';
import type { AutomationItem } from './store/types';
import { getAutomationEditorUrl } from './urls';

export const automationFields: Field<AutomationItem>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableGlobalSearch: true,
    enableSorting: true,
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
    render: ({ item }) =>
      item.description ? <div>{item.description}</div> : null,
  },
  {
    id: 'subscribers',
    label: __('Subscribers', 'mailpoet'),
    enableGlobalSearch: false,
    enableSorting: false,
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
    enableSorting: false,
    getValue: ({ item }) => item.status,
    render: ({ item }) => (
      <span className="mailpoet-automation-listing-cell-status">
        <AutomationStatusBadge status={item.status} />
      </span>
    ),
  },
];
