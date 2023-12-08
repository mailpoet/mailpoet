import { useState } from 'react';
import { __experimentalConfirmDialog } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { Item } from './item';
import { AutomationItem, storeName } from '../../store';
import { AutomationStatus } from '../../automation';

// With __experimentalConfirmDialog's type from build-types Typescript complains:
// JSX element type __experimentalConfirmDialog does not have any construct or call signatures
// Wrapping the type to React.FC fixes the issue
const ConfirmDialog = __experimentalConfirmDialog as React.FC<
  React.ComponentProps<typeof __experimentalConfirmDialog>
>;

export const useTrashButton = (
  automation: AutomationItem,
): Item | undefined => {
  const [showDialog, setShowDialog] = useState(false);
  const { trashAutomation, trashLegacyAutomation } = useDispatch(storeName);

  if (automation.status === AutomationStatus.TRASH) {
    return undefined;
  }

  const trash = automation.isLegacy ? trashLegacyAutomation : trashAutomation;

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
        title={__('Trash automation', 'mailpoet')}
        confirmButtonText={__('Yes, move to trash', 'mailpoet')}
        __experimentalHideHeader={false}
        onConfirm={() => trash(automation)}
        onCancel={() => setShowDialog(false)}
      >
        {sprintf(
          // translators: %s is the automation name
          __(
            'Are you sure you want to move the automation "%s" to the Trash?',
            'mailpoet',
          ),
          automation.name,
        )}
      </ConfirmDialog>
    ),
  };
};
