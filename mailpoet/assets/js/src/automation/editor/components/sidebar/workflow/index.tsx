import { PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store } from '../../../store';

export function WorkflowSidebar(): JSX.Element {
  const { workflowData } = useSelect(
    (select) => ({
      workflowData: select(store).getWorkflowData(),
    }),
    [],
  );

  return (
    <PanelBody>
      <div>
        <strong>{workflowData.name}</strong>
      </div>
      <br />
      <div>
        <strong>ID:</strong> {workflowData.id}
      </div>
      <div>
        <strong>Status:</strong> {workflowData.status}
      </div>
      <div>
        <strong>Created:</strong>{' '}
        {new Date(Date.parse(workflowData.created_at)).toLocaleString()}
      </div>
      <div>
        <strong>Updated:</strong>{' '}
        {new Date(Date.parse(workflowData.updated_at)).toLocaleString()}
      </div>
    </PanelBody>
  );
}
