import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Workflow, WorkflowStatus } from '../../workflow';

export const useRestoreButton = (workflow: Workflow): Item | undefined => {
  const { restoreWorkflow } = useDispatch(storeName);

  if (workflow.status !== WorkflowStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'restore',
    control: {
      title: __('Restore', 'mailpoet'),
      icon: null,
      onClick: () => restoreWorkflow(workflow, WorkflowStatus.DRAFT),
    },
  };
};
