import { __, sprintf } from '@wordpress/i18n';
import { Steps } from '../../../../../../../editor/components/automation/types';
import { MailPoet } from '../../../../../../../../mailpoet';
import { StepCell } from '../cells/step';
import { AutomationRunStatus } from '../../../../../../../components/status';
import { Log, NextStep } from '../../../../store';

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

export function transformLogsToRows(
  logs: Log[],
  steps: Steps,
  nextStep: NextStep,
) {
  const items = logs
    ? logs.map((log) => [
        {
          display: (
            <StepCell
              name={log.step_name || log.step_key}
              data={steps[log.step_id]}
            />
          ),
          value: log.step_name || log.step_key,
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
      ])
    : [];
  if (nextStep) {
    // translators: "Time left: 1 hour" or "Time left: 1 minute", uses WordPress' human_time_diff() to get the value
    const timeLeft = sprintf(
      __('Time left: %s', 'mailpoet'),
      nextStep.time_left,
    );
    items.push([
      {
        display: <StepCell name={nextStep.name} data={nextStep.step} />,
        value: nextStep.name,
      },
      {
        display: MailPoet.Date.format(new Date(logs.at(-1).updated_at)),
        value: logs.at(-1).updated_at,
      },
      {
        display: '-',
        value: '',
      },
      {
        display: (
          <>
            <AutomationRunStatus status="running" />
            <StatusInfo info={timeLeft} />
          </>
        ),
      },
    ]);
  }
  return items;
}
