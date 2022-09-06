import { useState } from 'react';
import {
  __experimentalConfirmDialog as ConfirmDialog,
  Button,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from '../../store';

export function TrashButton(): JSX.Element {
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);
  const { workflow } = useSelect(
    (select) => ({
      workflow: select(storeName).getWorkflowData(),
    }),
    [],
  );
  const { trash } = useDispatch(storeName);

  return (
    <>
      <ConfirmDialog
        isOpen={showConfirmDialog}
        title="Delete workflow"
        confirmButtonText="Yes, delete"
        onConfirm={async () => {
          trash(() => {
            setShowConfirmDialog(false);
          });
        }}
        onCancel={() => setShowConfirmDialog(false)}
        __experimentalHideHeader={false}
      >
        You are about to delete the “{workflow.name}” workflow.
      </ConfirmDialog>

      <Button
        isSecondary
        isDestructive
        onClick={() => setShowConfirmDialog(true)}
      >
        Move to Trash
      </Button>
    </>
  );
}
