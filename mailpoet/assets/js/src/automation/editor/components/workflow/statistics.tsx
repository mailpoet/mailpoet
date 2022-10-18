import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store';
import { Statistics as BaseStatistics } from '../../../components/statistics';

export function Statistics(): JSX.Element {
  const { workflow } = useSelect(
    (select) => ({
      workflow: select(storeName).getWorkflowData(),
    }),
    [],
  );

  return (
    <BaseStatistics
      items={[
        {
          key: 'entered',
          label: __('Total Entered', 'mailpoet'),
          value: workflow.stats.totals.entered,
        },
        {
          key: 'processing',
          label: __('Total Processing', 'mailpoet'),
          value: workflow.stats.totals.in_progress,
        },
        {
          key: 'exited',
          label: __('Total Exited', 'mailpoet'),
          value: workflow.stats.totals.exited,
        },
      ]}
    />
  );
}
