import { useState } from 'react';
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Workflow, WorkflowStatus } from '../../workflow';

export const useDeleteButton = (workflow: Workflow): Item | undefined => {
  const [showDialog, setShowDialog] = useState(false);
  const { deleteWorkflow } = useDispatch(storeName);

  if (workflow.status !== WorkflowStatus.TRASH) {
    return undefined;
  }

  return {
    key: 'delete',
    control: {
      title: __('Delete permanently', 'mailpoet'),
      icon: null,
      onClick: () => setShowDialog(true),
    },
    slot: (
      <ConfirmDialog
        isOpen={showDialog}
        title={__('Permanently delete automation', 'mailpoet')}
        confirmButtonText={__('Yes, permanently delete', 'mailpoet')}
        __experimentalHideHeader={false}
        onConfirm={() => deleteWorkflow(workflow)}
        onCancel={() => setShowDialog(false)}
      >
        {sprintf(
          // translators: %s is the automation name
          __(
            'Are you sure you want to permanently delete "%s" and all associated data? This cannot be undone!',
            'mailpoet',
          ),
          workflow.name,
        )}
      </ConfirmDialog>
    ),
  };
};
