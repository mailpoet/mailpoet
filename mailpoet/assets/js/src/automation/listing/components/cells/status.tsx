import { __ } from '@wordpress/i18n';
import { Workflow, WorkflowStatus } from '../../workflow';

type Props = {
  workflow: Workflow;
};

export function Status({ workflow }: Props): JSX.Element {
  return (
    <div className="mailpoet-automation-listing-cell-status">
      {workflow.status === WorkflowStatus.ACTIVE
        ? __('Active', 'mailpoet')
        : __('Not active', 'mailpoet')}
    </div>
  );
}
