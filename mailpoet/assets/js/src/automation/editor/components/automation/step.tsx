import classNames from 'classnames';
import { useContext } from 'react';
import { __unstableCompositeItem as CompositeItem } from '@wordpress/components';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import { blockMeta } from '@wordpress/icons';
import { __, _x } from '@wordpress/i18n';
import { AutomationCompositeContext } from './context';
import { StepFilters } from './step-filters';
import { StepMoreMenu } from './step-more-menu';
import { Step as StepData } from './types';
import { Chip } from '../chip';
import { ColoredIcon } from '../icons';
import { stepSidebarKey, storeName } from '../../store';
import { StepType } from '../../store/types';

const getUnknownStepType = (step: StepData): StepType => {
  const isTrigger = step.type === 'trigger';
  return {
    title: () =>
      isTrigger
        ? __('Unknown trigger', 'mailpoet')
        : __('Unknown step', 'mailpoet'),
    subtitle: () =>
      isTrigger
        ? __('Trigger type not registered', 'mailpoet')
        : __('Step type not registered', 'mailpoet'),
    description: () =>
      isTrigger
        ? __('Unknown trigger', 'mailpoet')
        : __('Unknown step', 'mailpoet'),
    keywords: [],
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
  context: 'edit' | 'view';
};

export function Step({ step, isSelected, context }: Props): JSX.Element {
  const { stepType, error } = useSelect(
    (select) => ({
      stepType: select(storeName).getStepType(step.key),
      error: select(storeName).getStepError(step.id),
    }),
    [step],
  );
  const { openSidebar, selectStep } = useDispatch(storeName);
  const compositeState = useContext(AutomationCompositeContext);
  const { batch } = useRegistry();

  const compositeItemId = `step-${step.id}`;
  const stepTypeData = stepType ?? getUnknownStepType(step);

  return (
    <div className="mailpoet-automation-editor-step-wrapper">
      <StepMoreMenu step={step} context={context} />
      <CompositeItem
        state={compositeState}
        role="treeitem"
        className={classNames({
          'mailpoet-automation-editor-step': true,
          'is-selected-step': isSelected,
          'is-unknown-step': !stepType,
        })}
        id={compositeItemId}
        key={step.id}
        focusable
        onClick={
          context === 'edit'
            ? () =>
                batch(() => {
                  openSidebar(stepSidebarKey);
                  selectStep(step);
                })
            : undefined
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
          <label
            htmlFor={compositeItemId}
            className="mailpoet-automation-editor-step-title"
          >
            {step.type !== 'trigger'
              ? stepTypeData.title(step, 'automation')
              : _x('Trigger', 'noun', 'mailpoet')}
          </label>
          <div className="mailpoet-automation-editor-step-subtitle">
            {step.type !== 'trigger'
              ? stepTypeData.subtitle(step, 'automation')
              : stepTypeData.title(step, 'automation')}
          </div>
        </div>
        <div className="mailpoet-automation-editor-step-footer">
          <StepFilters step={step} />
          {error && (
            <div className="mailpoet-automation-editor-step-error">
              <Chip variant="danger" size="small">
                {__('Not set', 'mailpoet')}
              </Chip>
            </div>
          )}
        </div>
      </CompositeItem>
    </div>
  );
}
