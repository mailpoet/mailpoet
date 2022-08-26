import classNames from 'classnames';
import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import { blockMeta } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { WorkflowCompositeContext } from './context';
import { Step as StepData } from './types';
import { ColoredIcon } from '../icons';
import { stepSidebarKey, store } from '../../store';
import { StepType } from '../../store/types';

const getUnknownStepType = (step: StepData): StepType => {
  const isTrigger = step.type === 'trigger';
  return {
    title: isTrigger
      ? __('Unknown trigger', 'mailpoet')
      : __('Unknown step', 'mailpoet'),
    subtitle: () =>
      isTrigger
        ? __('Trigger type not registered', 'mailpoet')
        : __('Step type not registered', 'mailpoet'),
    description: isTrigger
      ? __('Unknown trigger', 'mailpoet')
      : __('Unknown step', 'mailpoet'),
    group: step.type === 'trigger' ? 'triggers' : 'actions',
    key: step.key,
    foreground: '#8c8f94',
    background: '#dcdcde',
    edit: () => null,
    icon: () => blockMeta,
  };
};

type Props = {
  step: StepData;
  isSelected: boolean;
};

export function Step({ step, isSelected }: Props): JSX.Element {
  const { stepType } = useSelect(
    (select) => ({
      stepType: select(store).getStepType(step.key),
    }),
    [step],
  );
  const { openSidebar, selectStep } = useDispatch(store);
  const compositeState = useContext(WorkflowCompositeContext);
  const { batch } = useRegistry();

  const stepTypeData = stepType ?? getUnknownStepType(step);
  return (
    <CompositeItem
      state={compositeState}
      role="treeitem"
      className={classNames({
        'mailpoet-automation-editor-step': true,
        'is-selected-step': isSelected,
        'is-unknown-step': !stepType,
      })}
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
        <ColoredIcon
          icon={stepTypeData.icon}
          foreground={stepTypeData.foreground}
          background={stepTypeData.background}
          width="23px"
          height="23px"
        />
      </div>
      <div>
        <div className="mailpoet-automation-editor-step-title">
          {stepTypeData.title}
        </div>
        <div className="mailpoet-automation-editor-step-subtitle">
          {stepTypeData.subtitle(step)}
        </div>
      </div>
    </CompositeItem>
  );
}
