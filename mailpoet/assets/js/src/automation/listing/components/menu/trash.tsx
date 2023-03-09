import { useState } from 'react';
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Automation, AutomationStatus } from '../../automation';

export const useTrashButton = (automation: Automation): Item | undefined => {
  const [showDialog, setShowDialog] = useState(false);
  const { trashAutomation } = useDispatch(storeName);

  if (automation.status === AutomationStatus.TRASH) {
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
        title={__('Trash automation', 'mailpoet')}
        confirmButtonText={__('Yes, move to trash', 'mailpoet')}
        __experimentalHideHeader={false}
        onConfirm={() => trashAutomation(automation)}
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
