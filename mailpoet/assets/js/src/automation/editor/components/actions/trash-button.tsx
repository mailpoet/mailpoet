import { useState } from 'react';
import { __experimentalConfirmDialog, Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { storeName } from '../../store';

// With __experimentalConfirmDialog's type from build-types Typescript complains:
// JSX element type __experimentalConfirmDialog does not have any construct or call signatures
// Wrapping the type to React.FC fixes the issue
const ConfirmDialog = __experimentalConfirmDialog as React.FC<
  React.ComponentProps<typeof __experimentalConfirmDialog>
>;

export function TrashButton({
  performActionAfterDelete = () => {},
}): JSX.Element {
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);
  const [isBusy, setIsBusy] = useState(false);
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
          setIsBusy(true);
          void trash(() => {
            setShowConfirmDialog(false);
            setIsBusy(false);
            performActionAfterDelete();
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
        isBusy={isBusy}
        variant="secondary"
        isDestructive
        onClick={() => setShowConfirmDialog(true)}
      >
        {__('Move to Trash', 'mailpoet')}
      </Button>
    </>
  );
}
