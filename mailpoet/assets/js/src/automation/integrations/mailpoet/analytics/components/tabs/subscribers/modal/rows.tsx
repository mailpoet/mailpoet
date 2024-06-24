import { __ } from '@wordpress/i18n';
import { Log } from './types';
import { Steps } from '../../../../../../../editor/components/automation/types';
import { MailPoet } from '../../../../../../../../mailpoet';
import { StepCell } from '../cells/step';
import { StatusCell } from '../cells/status';

export const headers = [
  {
    key: 'step',
    isSortable: false,
    label: __('Automation step', 'mailpoet'),
  },
  {
    key: 'started_at',
    isSortable: false,
    label: __('Started on', 'mailpoet'),
  },
  {
    key: 'completed_at',
    isSortable: false,
    label: __('Completed on', 'mailpoet'),
  },
  {
    key: 'status',
    isSortable: false,
    label: __('Status', 'mailpoet'),
  },
];

export function transformLogsToRows(logs: Log[], steps: Steps) {
  return logs
    ? logs.map((log) => [
        {
          display: <StepCell name={log.step_key} data={steps[log.step_id]} />,
          value: log.step_key,
        },
        {
          display: MailPoet.Date.format(new Date(log.started_at)),
          value: log.started_at,
        },
        {
          display:
            log.status === 'complete'
              ? MailPoet.Date.format(new Date(log.updated_at))
              : '-',
          value: log.updated_at,
        },
        {
          display: <StatusCell status={log.status} />,
        },
      ])
    : [];
}
