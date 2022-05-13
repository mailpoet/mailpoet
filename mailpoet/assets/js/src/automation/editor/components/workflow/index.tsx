import { useSelect } from '@wordpress/data';
import { EmptyWorkflow } from './empty-workflow';
import { store } from '../../store';

export function Workflow(): JSX.Element {
  const { workflowData } = useSelect(
    (select) => ({
      workflowData: select(store).getWorkflowData(),
    }),
    [],
  );

  if (!workflowData) {
    return <EmptyWorkflow />;
  }

  return <div>{JSON.stringify(workflowData)}</div>;
}
