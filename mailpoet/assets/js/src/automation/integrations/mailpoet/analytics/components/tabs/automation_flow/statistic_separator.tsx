import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { AutomationFlowSection, storeName } from '../../../store';
import { storeName as editorStoreName } from '../../../../../../editor/store';
import { locale } from '../../../config';
import { Automation } from '../../../../../../editor/components/automation/types';

type Props = {
  previousStepId: string;
};
export function StatisticSeparator({
  previousStepId,
}: Props): JSX.Element | null {
  const { section, automation } = useSelect(
    (s) =>
      ({
        section: s(storeName).getSection('automation_flow'),
        automation: s(editorStoreName).getAutomationData(),
      } as {
        section: AutomationFlowSection;
        automation: Automation;
      }),
    [],
  );

  const { data } = section;
  if (!data) {
    return null;
  }

  const step = automation.steps[previousStepId];
  if (step?.type === 'trigger') {
    const formattedValue = Intl.NumberFormat(locale.toString(), {
      notation: 'compact',
    }).format(data.step_data.total);
    return (
      <div className="mailpoet-automation-editor-separator mailpoet-automation-analytics-separator">
        <p>
          <span className="mailpoet-automation-analytics-separator-values">
            {formattedValue}
          </span>
          <span className="mailpoet-automation-analytics-separator-text">
            {
              // translators: "entered" as in "100 people have entered this automation".
              __('entered', 'mailpoet')
            }
          </span>
        </p>
      </div>
    );
  }

  const flow = data.step_data?.flow;
  const value = flow !== undefined ? flow[previousStepId] ?? 0 : 0;
  const percent = Math.round((value / data.step_data.total) * 100);
  const formattedValue = Intl.NumberFormat(locale.toString(), {
    notation: 'compact',
  }).format(value);
  const formattedPercent = Intl.NumberFormat(locale.toString(), {
    style: 'percent',
  }).format(percent / 100);

  return (
    <div className="mailpoet-automation-editor-separator mailpoet-automation-analytics-separator">
      <p>
        <span className="mailpoet-automation-analytics-separator-values">
          {formattedPercent} ({formattedValue})
        </span>
        <span className="mailpoet-automation-analytics-separator-text">
          {
            // translators: "completed" as in "100 people have completed this step".
            __('completed', 'mailpoet')
          }
        </span>
      </p>
    </div>
  );
}
