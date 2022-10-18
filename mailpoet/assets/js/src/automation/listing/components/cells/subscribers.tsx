import { __ } from '@wordpress/i18n';
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
          label: __('Entered', 'mailpoet'),
          value: workflow.stats.totals.entered,
        },
        {
          key: 'processing',
          label: __('Processing', 'mailpoet'),
          value: workflow.stats.totals.in_progress,
        },
        {
          key: 'exited',
          label: __('Exited', 'mailpoet'),
          value: workflow.stats.totals.exited,
        },
      ]}
    />
  );
}
