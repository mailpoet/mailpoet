import { __ } from '@wordpress/i18n';
import { Steps } from '../../../../../../../editor/components/automation/types';
import { MailPoet } from '../../../../../../../../mailpoet';
import { StepCell } from '../cells/step';
import { AutomationRunStatus } from '../../../../../../../components/status';
import { Log } from '../../../../store';

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

function StatusInfo({ info }: { info: string | null }): JSX.Element {
  if (!info) {
    return null;
  }
  return (
    <div className="mailpoet-analytics-activity-modal-status-info">{info}</div>
  );
}

export function transformLogsToRows(logs: Log[], steps: Steps) {
  return logs.map((log) => [
    {
      display: <StepCell name={log.step_name} data={steps[log.step_id]} />,
      value: log.step_name,
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
      display: (
        <>
          <AutomationRunStatus status={log.status} />
          <StatusInfo info={log.error && log.error.message} />
        </>
      ),
    },
  ]);
}
