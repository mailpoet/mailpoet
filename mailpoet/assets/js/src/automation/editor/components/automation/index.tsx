import { Fragment, useMemo } from 'react';
import {
  __unstableComposite as Composite,
  __unstableUseCompositeState as useCompositeState,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, check } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { AutomationCompositeContext } from './context';
import { EmptyAutomation } from './empty-automation';
import { Separator } from './separator';
import { Step } from './step';
import { Step as StepData } from './types';
import { InserterPopover } from '../inserter-popover';
import { storeName } from '../../store';
import { AddTrigger } from './add-trigger';
import { Statistics } from './statistics';
import {
  RenderStepSeparatorType,
  RenderStepType,
} from '../../../types/filters';

type AutomationProps = {
  context: 'edit' | 'view';
};
export function Automation({ context }: AutomationProps): JSX.Element {
  const { automationData, selectedStep } = useSelect(
    (select) => ({
      automationData: select(storeName).getAutomationData(),
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const compositeState = useCompositeState({
    orientation: 'vertical',
    wrap: 'horizontal',
    shift: true,
  });

  const stepMap = automationData?.steps ?? undefined;

  // serialize steps (for now, we support only one trigger and linear automations)
  const steps = useMemo(() => {
    const stepArray = [stepMap.root];

    // eslint-disable-next-line no-constant-condition
    while (true) {
      const lastStep = stepArray[stepArray.length - 1];
      if (!lastStep || lastStep.next_steps.length === 0) {
        break;
      }
      stepArray.push(stepMap[lastStep.next_steps[0].id]);
    }
    return stepArray.slice(1);
  }, [stepMap]);

  const renderStep = useMemo(
    (): RenderStepType =>
      Hooks.applyFilters(
        'mailpoet.automation.render_step',
        (stepData: StepData) =>
          stepData.type === 'root' ? (
            <AddTrigger step={stepData} context={context} />
          ) : (
            <Step
              step={stepData}
              isSelected={selectedStep && stepData.id === selectedStep.id}
              context={context}
            />
          ),
        context,
      ),
    [selectedStep, context],
  );

  const renderSeparator = useMemo(
    (): RenderStepSeparatorType =>
      Hooks.applyFilters(
        'mailpoet.automation.render_step_separator',
        (previousStepData: StepData) => (
          <Separator previousStepId={previousStepData.id} />
        ),
        context,
      ),
    [context],
  );

  if (!automationData) {
    return <EmptyAutomation />;
  }

  return (
    <AutomationCompositeContext.Provider value={compositeState}>
      <Composite
        state={compositeState}
        role="tree"
        aria-label={__('Automation', 'mailpoet')}
        aria-orientation="vertical"
        className="mailpoet-automation-editor-automation"
      >
        <div className="mailpoet-automation-editor-automation-wrapper">
          <Statistics />
          {stepMap.root.next_steps.length === 0 ? (
            <>
              {renderStep(stepMap.root)}
              {renderSeparator(stepMap.root)}
            </>
          ) : (
            stepMap.root.next_steps.map(
              ({ id }) =>
                stepMap[id]?.type !== 'trigger' && (
                  <Fragment key={`root-${id}`}>
                    {renderStep(stepMap.root)}
                    {renderSeparator(stepMap.root)}
                  </Fragment>
                ),
            )
          )}
          {steps.map((step) => (
            <Fragment key={step.id}>
              {renderStep(step)}
              {renderSeparator(step)}
            </Fragment>
          ))}
          <Icon
            className="mailpoet-automation-editor-automation-end"
            icon={check}
          />
          <div />
        </div>
        <InserterPopover />
      </Composite>
    </AutomationCompositeContext.Provider>
  );
}
