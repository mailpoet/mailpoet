import { useCallback, useEffect, useState } from 'react';
import { dispatch, useSelect } from '@wordpress/data';
import { plus } from '@wordpress/icons';
import { Button } from '../../../components/button';
import { storeName } from '../../../../../editor/store';
import { MailPoet } from '../../../../../../mailpoet';

export function DesignEmailButton(): JSX.Element {
  const [isSaving, setIsSaving] = useState(false);

  const { selectedStep, workflowId, workflowSaved } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      workflowId: select(storeName).getWorkflowData().id,
      workflowSaved: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const emailId = selectedStep?.args?.email_id as string | undefined;
  const workflowStepId = selectedStep.id;

  const createEmail = useCallback(async () => {
    setIsSaving(true);
    const response = await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'automation',
        subject: '',
        options: {
          workflowId,
          workflowStepId,
        },
      },
    });

    dispatch(storeName).updateStepArgs(
      workflowStepId,
      'email_id',
      parseInt(response.data.id as string, 10),
    );

    dispatch(storeName).save();
  }, [workflowId, workflowStepId]);

  // This component is rendered only when no email ID is set. Once we have the ID
  // and the workflow is saved, we can safely redirect to the email design flow.
  useEffect(() => {
    if (emailId && workflowSaved) {
      window.location.href = `admin.php?page=mailpoet-newsletters#/template/${emailId}`;
    }
  }, [emailId, workflowSaved]);

  return (
    <Button
      variant="sidebar-primary"
      centered
      icon={plus}
      onClick={createEmail}
      isBusy={isSaving}
      disabled={isSaving}
    >
      Design email
    </Button>
  );
}
