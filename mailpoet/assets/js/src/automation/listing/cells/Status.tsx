import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { Workflow, WorkflowStatus } from '../workflow';

type Props = {
  workflow: Workflow;
};

export function Status({ workflow }: Props): JSX.Element {
  const [isActive, setIsActive] = useState(false);

  return (
    <div>
      <ToggleControl
        checked={isActive}
        onChange={(active) => setIsActive(active)}
      />
      {workflow.status === WorkflowStatus.ACTIVE
        ? __('Active', 'mailpoet')
        : __('Not active', 'mailpoet')}
    </div>
  );
}
