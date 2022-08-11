import { PanelBody, PanelRow } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store } from '../../../store';
import { PlainBodyTitle } from '../../panel';

export function WorkflowSidebar(): JSX.Element {
  const { workflowData } = useSelect(
    (select) => ({
      workflowData: select(store).getWorkflowData(),
    }),
    [],
  );

  const dateOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  };

  return (
    <PanelBody>
      <PlainBodyTitle title="Automation details" />
      <PanelRow>
        <strong>Date added</strong>{' '}
        {new Date(Date.parse(workflowData.created_at)).toLocaleDateString(
          undefined,
          dateOptions,
        )}
      </PanelRow>
      <PanelRow>
        <strong>Activated</strong>{' '}
        {workflowData.status === 'active' &&
          new Date(Date.parse(workflowData.updated_at)).toLocaleDateString(
            undefined,
            dateOptions,
          )}
        {workflowData.status !== 'active' &&
          workflowData.activated_at &&
          new Date(Date.parse(workflowData.activated_at)).toLocaleDateString(
            undefined,
            dateOptions,
          )}
        {workflowData.status !== 'active' && !workflowData.activated_at && (
          <span className="mailpoet-deactive">Not activated yet.</span>
        )}
      </PanelRow>
      <PanelRow>
        <strong>Author</strong> {workflowData.author.name}
      </PanelRow>
    </PanelBody>
  );
}
