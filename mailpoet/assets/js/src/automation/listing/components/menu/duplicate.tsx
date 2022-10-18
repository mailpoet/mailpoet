import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Workflow, WorkflowStatus } from '../../workflow';

export const useDuplicateButton = (workflow: Workflow): Item | undefined => {
  const { duplicateWorkflow } = useDispatch(storeName);

  if (workflow.status === WorkflowStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'duplicate',
    control: {
      title: __('Duplicate', 'mailpoet'),
      icon: null,
      onClick: () => duplicateWorkflow(workflow),
    },
  };
};
