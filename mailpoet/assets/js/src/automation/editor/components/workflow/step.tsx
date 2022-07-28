import { ComponentType, useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { useDispatch, useRegistry, select } from '@wordpress/data';
import { WorkflowCompositeContext } from './context';
import { Step as StepData } from './types';
import { stepSidebarKey, store } from '../../store';
import { TriggerIcon } from '../icons';

// mocked data
function getIcon(step: StepData): JSX.Element | ComponentType | null {
  if (step.type === 'trigger') {
    return <TriggerIcon />;
  }
  const selectedStepType = select(store).getStepType(step.key);
  return selectedStepType ? selectedStepType.icon : null;
}

// mocked data
function getTitle(step: StepData): string {
  if (step.type === 'trigger') {
    return 'Trigger';
  }
  const selectedStepType = select(store).getStepType(step.key);
  return selectedStepType ? selectedStepType.title : '';
}

// mocked data
function getSubtitle(step: StepData): string {
  if (step.key === 'mailpoet:segment:subscribed') {
    return 'Subscribed to segment';
  }
  const selectedStepType = select(store).getStepType(step.key);
  return selectedStepType ? selectedStepType.subtitle(step) : '';
}

type Props = {
  step: StepData;
  isSelected: boolean;
};

export function Step({ step, isSelected }: Props): JSX.Element {
  const { openSidebar, selectStep } = useDispatch(store);
  const compositeState = useContext(WorkflowCompositeContext);
  const { batch } = useRegistry();

  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className={`mailpoet-automation-editor-step ${
        isSelected ? 'selected-step' : ''
      }`}
      key={step.id}
      focusable
      onClick={() =>
        batch(() => {
          openSidebar(stepSidebarKey);
          selectStep(step);
        })
      }
    >
      <div className="mailpoet-automation-editor-step-icon">
        {getIcon(step)}
      </div>
      <div>
        <div className="mailpoet-automation-editor-step-title">
          {getTitle(step)}
        </div>
        <div className="mailpoet-automation-editor-step-subtitle">
          {getSubtitle(step)}
        </div>
      </div>
    </CompositeItem>
  );
}
