import classNames from 'classnames';
import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import { blockMeta } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { WorkflowCompositeContext } from './context';
import { StepMoreMenu } from './step-more-menu';
import { Step as StepData } from './types';
import { ColoredIcon } from '../icons';
import { stepSidebarKey, storeName } from '../../store';
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
      stepType: select(storeName).getStepType(step.key),
    }),
    [step],
  );
  const { openSidebar, selectStep } = useDispatch(storeName);
  const compositeState = useContext(WorkflowCompositeContext);
  const { batch } = useRegistry();

  const stepTypeData = stepType ?? getUnknownStepType(step);
  return (
    <div className="mailpoet-automation-editor-step-wrapper">
      <StepMoreMenu step={step} />
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
            {step.type !== 'trigger'
              ? stepTypeData.title
              : __('Trigger', 'mailpoet')}
          </div>
          <div className="mailpoet-automation-editor-step-subtitle">
            {step.type !== 'trigger'
              ? stepTypeData.subtitle(step)
              : stepTypeData.title}
          </div>
        </div>
      </CompositeItem>
    </div>
  );
}
