import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Workflow, WorkflowStatus } from '../../workflow';

export const useTrashButton = (workflow: Workflow): Item | undefined => {
  const { trashWorkflow } = useDispatch(storeName);

  if (workflow.status === WorkflowStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'trash',
    control: {
      title: __('Trash', 'mailpoet'),
      icon: null,
      onClick: () => trashWorkflow(workflow),
    },
  };
};
