import { Notice, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { storeName } from '../../../store';
import { triggerFilterStrings } from '../../automation/trigger-filters';
import { FiltersPanel } from '../../filters';
import { StepCard } from '../../step-card';

function StepSidebarGeneralError(): JSX.Element {
  const { errors } = useSelect(
    (select) => ({
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      ),
    }),
    [],
  );

  if (!errors || !errors?.fields) {
    return null;
  }

  const errorMessage = errors.fields?.general;
  if (!errorMessage) {
    return null;
  }

  return (
    <Notice isDismissible={false} status="error">
      {errorMessage}
    </Notice>
  );
}

export function StepSidebar(): JSX.Element {
  const { selectedStep, selectedStepType } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      selectedStepType: select(storeName).getSelectedStepType(),
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
      <StepSidebarGeneralError />
      <StepCard
        title={selectedStepType.title(selectedStep, 'sidebar')}
        description={selectedStepType.description(selectedStep, 'sidebar')}
        icon={selectedStepType.icon}
      />
      <Edit
        // Force sidebar remount to avoid different steps mixing their data.
        // This can happen e.g. when having "useState" or "useRef" internally.
        key={selectedStep.id}
      />

      {selectedStep.type === 'trigger' && (
        <FiltersPanel strings={triggerFilterStrings} />
      )}
    </div>
  );
}
