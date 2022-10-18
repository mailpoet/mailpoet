import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Workflow, WorkflowStatus } from '../../workflow';

export const useDeleteButton = (workflow: Workflow): Item | undefined => {
  const { deleteWorkflow } = useDispatch(storeName);

  if (workflow.status !== WorkflowStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'delete',
    control: {
      title: __('Delete permanently', 'mailpoet'),
      icon: null,
      onClick: () => deleteWorkflow(workflow),
    },
  };
};
