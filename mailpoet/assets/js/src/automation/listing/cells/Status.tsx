import { __ } from '@wordpress/i18n';
import { Toggle } from '../../../common';
import { Workflow, WorkflowStatus } from '../workflow';

type Props = {
  workflow: Workflow;
};

export function Status({ workflow }: Props): JSX.Element {
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
