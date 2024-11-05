import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { AutomationFlowSection, storeName } from '../../../store';
import { storeName as editorStoreName } from '../../../../../../editor/store';
import { locale } from '../../../config';
import { Step } from '../../../../../../editor/components/automation/types';

type Props = {
  previousStep: Step;
  index: number;
  nextStep?: Step;
};

export function StatisticSeparator({
  previousStep,
  index,
  nextStep,
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

  const completed = data.step_data?.completed || {};
  const failed = data.step_data?.failed || {};
  const waiting = data.step_data?.waiting || {};
  const calculateTotals = (id) =>
    (completed[id] ?? 0) + (failed[id] ?? 0) + (waiting[id] ?? 0);
  let totalEntered = 0;
  if (nextStep) {
    totalEntered = calculateTotals(nextStep.id);
  } else if (previousStep.next_steps.length === 2) {
    // When there is no next step and the previous step has 2+ next steps we are
    // in an empty if/else branch. To calculate the total we need to subtract
    // totalEntered of the sibling step from totalEntered of previousStep
    const siblingStep = previousStep.next_steps.find((step) => step.id);
    const totalEnteredSibling = siblingStep
      ? calculateTotals(siblingStep.id)
      : 0;
    const totalEnteredPrevious = completed[previousStep.id] ?? 0;
    totalEntered = totalEnteredPrevious - totalEnteredSibling;
  } else {
    totalEntered = completed[previousStep.id] ?? 0;
  }
  const percent =
    data.step_data.total > 0
      ? Math.round((totalEntered / data.step_data.total) * 100)
      : 0;
  const formattedValue = Intl.NumberFormat(locale.toString(), {
    notation: 'compact',
  }).format(totalEntered);
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
