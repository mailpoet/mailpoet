import { Fragment, useMemo } from 'react';
import {
  __unstableComposite as Composite,
  __unstableUseCompositeState as useCompositeState,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { EditorNotices } from '@wordpress/editor';
import { WorkflowCompositeContext } from './context';
import { EmptyWorkflow } from './empty-workflow';
import { Separator } from './separator';
import { Step } from './step';
import { InserterPopover } from '../inserter-popover';
import { store } from '../../store';

export function Workflow(): JSX.Element {
  const { workflowData, selectedStep } = useSelect(
    (select) => ({
      workflowData: select(store).getWorkflowData(),
      selectedStep: select(store).getSelectedStep(),
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
          {steps.map((step, i) => (
            <Fragment key={step.id}>
              {i > 0 && <Separator />}
              <Step
                step={step}
                isSelected={selectedStep && step.id === selectedStep.id}
              />
            </Fragment>
          ))}
          <div />
        </div>
        <InserterPopover />
      </Composite>
    </WorkflowCompositeContext.Provider>
  );
}
