import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store } from '../../../store';

export function StepSidebar(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(store).getSelectedStep(),
    }),
    [],
  );

  if (!selectedStep) {
    return <PanelBody>No step selected.</PanelBody>;
  }

  return (
    <PanelBody>
      <div>
        <strong>ID:</strong> {selectedStep.id}
      </div>
      <div>
        <strong>Type:</strong> {selectedStep.type}
      </div>
      <div>
        <strong>Key:</strong> {selectedStep.key}
      </div>
      <div>
        <strong>Args:</strong> {JSON.stringify(selectedStep.args)}
      </div>
    </PanelBody>
  );
}
