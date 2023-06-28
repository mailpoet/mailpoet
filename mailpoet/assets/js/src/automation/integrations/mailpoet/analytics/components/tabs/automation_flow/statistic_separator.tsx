import { useSelect } from '@wordpress/data';
import { storeName as editorStoreName } from '../../../../../../editor/store/constants';

type Props = {
  previousStepId: string;
};
export function StatisticSeparator({
  previousStepId,
}: Props): JSX.Element | null {
  const { automation } = useSelect(
    (s) => ({
      automation: s(editorStoreName).getAutomationData(),
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
