import { _x } from '@wordpress/i18n';
import { Automation } from '../../automation';
import { Statistics } from '../../../components/statistics';

type Props = {
  automation: Automation;
};

export function Subscribers({ automation }: Props): JSX.Element {
  return (
    <Statistics
      labelPosition="after"
      items={[
        {
          key: 'entered',
          // translators: Total number of subscribers who entered an automation
          label: _x('Entered', 'automation stats', 'mailpoet'),
          value: automation.stats.totals.entered,
        },
        {
          key: 'processing',
          // translators: Total number of subscribers who are being processed in an automation
          label: _x('Processing', 'automation stats', 'mailpoet'),
          value: automation.stats.totals.in_progress,
        },
        {
          key: 'exited',
          // translators: Total number of subscribers who exited an automation, no matter the result
          label: _x('Exited', 'automation stats', 'mailpoet'),
          value: automation.stats.totals.exited,
        },
      ]}
    />
  );
}
