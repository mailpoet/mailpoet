import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { useDispatch, useRegistry } from '@wordpress/data';
import { WorkflowCompositeContext } from './context';
import { Step as StepType } from './types';
import { DelayIcon, EmailIcon, TriggerIcon } from '../icons';
import { stepSidebarKey, store } from '../../store';

// mocked data
function getIcon(step: StepType): JSX.Element | null {
  if (step.type === 'trigger') {
    return <TriggerIcon />;
  }

  if (step.key === 'core:delay') {
    return <DelayIcon />;
  }

  if (step.key === 'mailpoet:send-email') {
    return <EmailIcon />;
  }

  return null;
}

// mocked data
function getTitle(step: StepType): string {
  if (step.type === 'trigger') {
    return 'Trigger';
  }

  if (step.key === 'core:delay') {
    return 'Delay';
  }

  if (step.key === 'mailpoet:send-email') {
    return 'Send email';
  }

  return '';
}

// mocked data
function getSubtitle(step: StepType): string {
  if (step.key === 'mailpoet:segment:subscribed') {
    return 'Subscribed to segment';
  }
  if (step.key === 'core:delay') {
    return `${step.args.seconds as number} seconds`;
  }
  if (step.key === 'mailpoet:send-email') {
    return `Email ID: ${step.args.email_id as number}`;
  }
  return step.key;
}

type Props = {
  step: StepType;
};

export function Step({ step }: Props): JSX.Element {
  const { openSidebar, selectStep } = useDispatch(store);
  const compositeState = useContext(WorkflowCompositeContext);
  const { batch } = useRegistry();

  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className="mailpoet-automation-editor-step"
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
