import { useState } from 'react';
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Workflow, WorkflowStatus } from '../../workflow';

export const useTrashButton = (workflow: Workflow): Item | undefined => {
  const [showDialog, setShowDialog] = useState(false);
  const { trashWorkflow } = useDispatch(storeName);

  if (workflow.status === WorkflowStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'trash',
    control: {
      title: _x('Trash', 'verb', 'mailpoet'),
      icon: null,
      onClick: () => setShowDialog(true),
    },
    slot: (
      <ConfirmDialog
        isOpen={showDialog}
        title={__('Trash workflow', 'mailpoet')}
        confirmButtonText={__('Yes, move to trash', 'mailpoet')}
        __experimentalHideHeader={false}
        onConfirm={() => trashWorkflow(workflow)}
        onCancel={() => setShowDialog(false)}
      >
        {sprintf(
          // translators: %s is the workflow name
          __(
            'Are you sure you want to move the workflow "%s" to the Trash?',
            'mailpoet',
          ),
          workflow.name,
        )}
      </ConfirmDialog>
    ),
  };
};
