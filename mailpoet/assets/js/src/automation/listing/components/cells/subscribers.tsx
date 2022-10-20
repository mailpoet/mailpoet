import { _x } from '@wordpress/i18n';
import { Workflow } from '../../workflow';
import { Statistics } from '../../../components/statistics';

type Props = {
  workflow: Workflow;
};

export function Subscribers({ workflow }: Props): JSX.Element {
  return (
    <Statistics
      labelPosition="after"
      items={[
        {
          key: 'entered',
          // translators: Total number of subscribers who entered an automation workflow
          label: _x('Entered', 'automation stats', 'mailpoet'),
          value: workflow.stats.totals.entered,
        },
        {
          key: 'processing',
          // translators: Total number of subscribers who are being processed in an automation workflow
          label: _x('Processing', 'automation stats', 'mailpoet'),
          value: workflow.stats.totals.in_progress,
        },
        {
          key: 'exited',
          // translators: Total number of subscribers who exited an automation workflow, no matter the result
          label: _x('Exited', 'automation stats', 'mailpoet'),
          value: workflow.stats.totals.exited,
        },
      ]}
    />
  );
}
