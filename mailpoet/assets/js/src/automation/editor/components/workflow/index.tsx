import {
  __unstableComposite as Composite,
  __unstableUseCompositeState as useCompositeState,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { WorkflowCompositeContext } from './context';
import { EmptyWorkflow } from './empty-workflow';
import { store } from '../../store';

export function Workflow(): JSX.Element {
  const { workflowData } = useSelect(
    (select) => ({
      workflowData: select(store).getWorkflowData(),
    }),
    [],
  );

  const compositeState = useCompositeState({
    orientation: 'vertical',
    wrap: 'horizontal',
    shift: true,
  });

  if (!workflowData) {
    return <EmptyWorkflow />;
  }

  return (
    <WorkflowCompositeContext.Provider value={compositeState}>
      <Composite
        state={compositeState}
        role="tree"
        aria-orientation="vertical"
        className="mailpoet-automation-editor-workflow"
      >
        {JSON.stringify(workflowData)}
      </Composite>
    </WorkflowCompositeContext.Provider>
  );
}
