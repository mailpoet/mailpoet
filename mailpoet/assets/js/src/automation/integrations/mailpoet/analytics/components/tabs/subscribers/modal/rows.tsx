import { __, sprintf } from '@wordpress/i18n';
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
  return logs.map((log) => {
    const timeLeft = log.time_left
      ? // translators: "Time left: 1 hour" or "Time left: 1 minute", uses WordPress' human_time_diff() to get the value
        sprintf(__('Time left: %s', 'mailpoet'), log.time_left)
      : null;
    let statusInfo = null;
    if (log.error) {
      statusInfo = log.error.message ? log.error.message : null;
    } else {
      statusInfo = timeLeft;
    }

    return [
      {
        display: <StepCell name={log.step_name} data={steps[log.step_id]} />,
        value: log.step_name,
      },
      {
        display: MailPoet.Date.formatFromGmt(new Date(log.started_at)),
        value: log.started_at,
      },
      {
        display:
          log.status === 'complete'
            ? MailPoet.Date.formatFromGmt(new Date(log.updated_at))
            : '-',
        value: log.updated_at,
      },
      {
        display: (
          <>
            <AutomationRunStatus status={log.status} />
            <StatusInfo info={statusInfo} />
          </>
        ),
      },
    ];
  });
}
