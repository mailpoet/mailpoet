import { useSelect } from '@wordpress/data';
import { storeName } from '../../../store';

type Props = {
  previousStepId: string;
};
export function StatisticSeparator({
  previousStepId,
}: Props): JSX.Element | null {
  const { automation } = useSelect(
    (s) => ({
      automation: s(storeName).getAutomation(),
    }),
    [],
  );
  const step = automation.steps[previousStepId];
  if (!step) {
    return null;
  }

  return (
    <div className="mailpoet-automation-editor-separator">
      <p>{`statistics for ${step.key}`}</p>
    </div>
  );
}
