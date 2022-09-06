import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import {
  Button,
  __experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { StoreDescriptor, useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { addQueryArgs } from '@wordpress/url';
import { storeName } from '../../store';
import { Workflow } from '../workflow/types';
import { MailPoet } from '../../../../mailpoet';
import { LISTING_NOTICE_PARAMETERS } from '../../../listing/workflow-listing-notices';

export function TrashButton(): JSX.Element {
  const [showConfirmDialog, setShowConfirmDialog] = useState(false);
  const { createErrorNotice } = useDispatch(noticesStore as StoreDescriptor);
  const { workflow } = useSelect(
    (select) => ({
      workflow: select(storeName).getWorkflowData(),
    }),
    [],
  );

  const trash = () => {
    apiFetch({
      path: `/workflows/${workflow.id}`,
      method: 'PUT',
      data: {
        ...workflow,
        status: 'trash',
      },
    })
      .then(({ data }: { data: Workflow }) => {
        if (data.status !== 'trash') {
          void createErrorNotice('An error occurred!', {
            explicitDismiss: true,
          });
          return;
        }

        window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
          [LISTING_NOTICE_PARAMETERS.workflowDeleted]: workflow.id,
        });
      })
      .catch((): void => {
        void createErrorNotice('An error occurred!', {
          explicitDismiss: true,
        });
      })
      .finally(() => {
        setShowConfirmDialog(false);
      });

    return true;
  };

  return (
    <>
      <ConfirmDialog
        isOpen={showConfirmDialog}
        title="Delete workflow"
        confirmButtonText="Yes, delete"
        onConfirm={trash}
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
