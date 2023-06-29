import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { AutomationFlowSection, storeName } from '../../../store';
import { locale } from '../../../config';
import { Step } from '../../../../../../editor/components/automation/types';

export function StepFooter({ step }: { step: Step }): JSX.Element | null {
  const { section } = useSelect(
    (s) =>
      ({
        section: s(storeName).getSection('automation_flow'),
      } as {
        section: AutomationFlowSection;
      }),
    [],
  );

  const { data } = section;
  if (!data || step.type === 'trigger') {
    return null;
  }
  const waiting = data.step_data?.waiting;
  const value = waiting !== undefined ? waiting[step.id] ?? 0 : 0;
  const percent = Math.round((value / data.step_data.total) * 100);

  const formattedValue = Intl.NumberFormat(locale.toString(), {
    notation: 'compact',
  }).format(value);
  const formattedPercent = Intl.NumberFormat(locale.toString(), {
    style: 'percent',
  }).format(percent / 100);
  return (
    <div className="mailpoet-automation-analytics-step-footer">
      <p>
        {formattedPercent} ({formattedValue}){' '}
        <span>
          {
            // translators: "waiting" as in "100 people are waiting for this step".
            __('waiting', 'mailpoet')
          }
        </span>
      </p>
    </div>
  );
}
