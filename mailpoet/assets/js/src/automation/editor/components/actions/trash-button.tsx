import { useState } from 'react';
import {
  __experimentalConfirmDialog as ConfirmDialog,
  Button,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { storeName } from '../../store';

export function TrashButton(): JSX.Element {
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);
  const { automation } = useSelect(
    (select) => ({
      automation: select(storeName).getAutomationData(),
    }),
    [],
  );
  const { trash } = useDispatch(storeName);

  return (
    <>
      <ConfirmDialog
        isOpen={showConfirmDialog}
        title={__('Delete automation', 'mailpoet')}
        confirmButtonText={__('Yes, delete', 'mailpoet')}
        onConfirm={async () => {
          trash(() => {
            setShowConfirmDialog(false);
          });
        }}
        onCancel={() => setShowConfirmDialog(false)}
        __experimentalHideHeader={false}
      >
        {sprintf(
          __('You are about to delete the automation "%s".', 'mailpoet'),
          automation.name,
        )}
        <br />
        {__(' This will stop it for all subscribers immediately.', 'mailpoet')}
      </ConfirmDialog>

      <Button
        variant="secondary"
        isDestructive
        onClick={() => setShowConfirmDialog(true)}
      >
        {__('Move to Trash', 'mailpoet')}
      </Button>
    </>
  );
}
