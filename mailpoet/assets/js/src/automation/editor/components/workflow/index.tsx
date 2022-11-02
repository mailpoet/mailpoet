import { Fragment, useMemo } from 'react';
import {
  __unstableComposite as Composite,
  __unstableUseCompositeState as useCompositeState,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Icon, check } from '@wordpress/icons';
import { Hooks } from 'wp-js-hooks';
import { WorkflowCompositeContext } from './context';
import { EmptyWorkflow } from './empty-workflow';
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

export function Workflow(): JSX.Element {
  const { workflowData, selectedStep } = useSelect(
    (select) => ({
      workflowData: select(storeName).getWorkflowData(),
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const compositeState = useCompositeState({
    orientation: 'vertical',
    wrap: 'horizontal',
    shift: true,
  });

  const stepMap = workflowData?.steps ?? undefined;

  // serialize steps (for now, we support only one trigger and linear workflows)
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
        'mailpoet.automation.workflow.render_step',
        (stepData: StepData) =>
          stepData.type === 'root' ? (
            <AddTrigger step={stepData} />
          ) : (
            <Step
              step={stepData}
              isSelected={selectedStep && stepData.id === selectedStep.id}
            />
          ),
      ),
    [selectedStep],
  );

  const renderSeparator = useMemo(
    (): RenderStepSeparatorType =>
      Hooks.applyFilters(
        'mailpoet.automation.workflow.render_step_separator',
        (previousStepData: StepData) => (
          <Separator previousStepId={previousStepData.id} />
        ),
      ),
    [],
  );

  if (!workflowData) {
    return <EmptyWorkflow />;
  }

  return (
    <WorkflowCompositeContext.Provider value={compositeState}>
      <Composite
        state={compositeState}
        role="tree"
        aria-label={__('Automation', 'mailpoet')}
        aria-orientation="vertical"
        className="mailpoet-automation-editor-workflow"
      >
        <div className="mailpoet-automation-editor-workflow-wrapper">
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
            className="mailpoet-automation-editor-workflow-end"
            icon={check}
          />
          <div />
        </div>
        <InserterPopover />
      </Composite>
    </WorkflowCompositeContext.Provider>
  );
}
