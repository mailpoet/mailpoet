import { __ } from '@wordpress/i18n';
import { Toggle } from '../../../common';
import { WorkflowProps, WorkflowStatus } from '../workflow';

export function Status({ workflow }: WorkflowProps): JSX.Element {
  const toggleStatus = () => {
    // @Todo
  };

  return (
    <div>
      <Toggle onCheck={toggleStatus} />
      {workflow.status === WorkflowStatus.ACTIVE
        ? __('Active', 'mailpoet')
        : __('Not active', 'mailpoet')}
    </div>
  );
}
