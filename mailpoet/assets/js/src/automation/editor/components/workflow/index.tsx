import { Fragment, useMemo } from 'react';
import {
  __unstableComposite as Composite,
  __unstableUseCompositeState as useCompositeState,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Icon, check } from '@wordpress/icons';
import { EditorNotices } from '@wordpress/editor';
import { Hooks } from 'wp-js-hooks';
import { WorkflowCompositeContext } from './context';
import { EmptyWorkflow } from './empty-workflow';
import { Separator } from './separator';
import { Step } from './step';
import { Step as StepData } from './types';
import { InserterPopover } from '../inserter-popover';
import { storeName } from '../../store';
import { AddTrigger } from './add-trigger';

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

  const triggers = useMemo(
    () => Object.values(stepMap ?? {}).filter(({ type }) => type === 'trigger'),
    [stepMap],
  );

  // serialize steps (for now, we support only one trigger and linear workflows)
  const steps = useMemo(() => {
    if (!stepMap || triggers.length < 1) {
      return [];
    }

    const stepArray = [triggers[0]];

    // eslint-disable-next-line no-constant-condition
    while (true) {
      const lastStep = stepArray[stepArray.length - 1];
      if (
        !('next_step_id' in lastStep) ||
        !lastStep.next_step_id ||
        !(stepMap[lastStep.next_step_id] ?? false)
      ) {
        break;
      }
      stepArray.push(stepMap[lastStep.next_step_id]);
    }
    return stepArray;
  }, [triggers, stepMap]);

  const renderStep = useMemo(
    () =>
      Hooks.applyFilters(
        'mailpoet.automation.workflow.render_step',
        (stepData: StepData) =>
          stepData.type === 'trigger' && stepData.key === 'core:empty' ? (
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

  if (!workflowData) {
    return <EmptyWorkflow />;
  }

  return (
    <WorkflowCompositeContext.Provider value={compositeState}>
      <EditorNotices />
      <Composite
        state={compositeState}
        role="tree"
        aria-orientation="vertical"
        className="mailpoet-automation-editor-workflow"
      >
        <div className="mailpoet-automation-editor-workflow-wrapper">
          <div />
          {steps.map((step) => (
            <Fragment key={step.id}>
              {renderStep(step)}
              <Separator previousStepId={step.id} />
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
