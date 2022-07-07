import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store } from '../../../store';
import { StepCard } from '../../step-card';

export function StepSidebar(): JSX.Element {
  const { selectedStep, selectedStepType } = useSelect(
    (select) => ({
      selectedStep: select(store).getSelectedStep(),
      selectedStepType: select(store).getSelectedStepType(),
    }),
    [],
  );

  if (!selectedStep) {
    return <PanelBody>No step selected.</PanelBody>;
  }

  return (
    <div className="block-editor-block-inspector">
      <StepCard
        title={selectedStepType.title}
        description={selectedStepType.description}
        icon={selectedStepType.icon}
      />

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
    </div>
  );
}
