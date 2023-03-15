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

  if (!selectedStepType) {
    return <PanelBody>Unknown step type.</PanelBody>;
  }

  const Edit = selectedStepType.edit;

  return (
    <div className="block-editor-block-inspector">
      <StepCard
        title={selectedStepType.title}
        description={selectedStepType.description}
        icon={selectedStepType.icon}
      />

      <Edit
        // Force sidebar remount to avoid different steps mixing their data.
        // This can happen e.g. when having "useState" or "useRef" internally.
        key={selectedStep.id}
      />
    </div>
  );
}
