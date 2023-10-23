import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { AutomationFlowSection, storeName } from '../../../store';
import { storeName as editorStoreName } from '../../../../../../editor/store';
import { locale } from '../../../config';
import { Step } from '../../../../../../editor/components/automation/types';

type Props = {
  previousStep: Step;
  index: number;
};

export function StatisticSeparator({
  previousStep,
  index,
}: Props): JSX.Element | null {
  const { section, stepType } = useSelect(
    (s) => ({
      section: s(storeName).getSection(
        'automation_flow',
      ) as AutomationFlowSection,
      stepType: s(editorStoreName).getStepType(previousStep.key),
    }),
    [],
  );

  const { data } = section;
  if (!data) {
    return null;
  }

  if (previousStep.type === 'trigger') {
    const formattedValue = Intl.NumberFormat(locale.toString(), {
      notation: 'compact',
    }).format(data.step_data.total);
    return (
      <div
        className={`mailpoet-automation-editor-separator mailpoet-automation-analytics-separator mailpoet-automation-analytics-separator-${previousStep.id}`}
      >
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
  const value = flow !== undefined ? flow[previousStep.id] ?? 0 : 0;
  const percent =
    data.step_data.total > 0
      ? Math.round((value / data.step_data.total) * 100)
      : 0;
  const formattedValue = Intl.NumberFormat(locale.toString(), {
    notation: 'compact',
  }).format(value);
  const formattedPercent = Intl.NumberFormat(locale.toString(), {
    style: 'percent',
  }).format(percent / 100);

  const BranchBadge =
    previousStep.next_steps.length > 1 && stepType?.branchBadge;

  return (
    <>
      {BranchBadge && (
        <div className="mailpoet-automation-editor-branch-badge">
          <BranchBadge step={previousStep} index={index} />
        </div>
      )}
      <div
        className={`mailpoet-automation-editor-separator mailpoet-automation-analytics-separator  mailpoet-automation-analytics-separator-${previousStep.id}`}
      >
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
    </>
  );
}
