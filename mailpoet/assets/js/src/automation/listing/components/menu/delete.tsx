import { useState } from 'react';
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Item } from './item';
import { storeName } from '../../store';
import { Automation, AutomationStatus } from '../../automation';

export const useDeleteButton = (automation: Automation): Item | undefined => {
  const [showDialog, setShowDialog] = useState(false);
  const { deleteAutomation } = useDispatch(storeName);

  if (automation.status !== AutomationStatus.TRASH) {
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
        onConfirm={() => deleteAutomation(automation)}
        onCancel={() => setShowDialog(false)}
      >
        {sprintf(
          // translators: %s is the automation name
          __(
            'Are you sure you want to permanently delete "%s" and all associated data? This cannot be undone!',
            'mailpoet',
          ),
          automation.name,
        )}
      </ConfirmDialog>
    ),
  };
};
