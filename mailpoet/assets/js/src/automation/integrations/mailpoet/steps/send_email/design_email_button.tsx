import { useCallback, useEffect, useState } from 'react';
import { dispatch, useSelect } from '@wordpress/data';
import { plus } from '@wordpress/icons';
import { Button } from '../../components/button';
import { store } from '../../../../editor/store';
import { MailPoet } from '../../../../../mailpoet';

export function DesignEmailButton(): JSX.Element {
  const [isSaving, setIsSaving] = useState(false);

  const { selectedStep, workflowSaved } = useSelect(
    (select) => ({
      selectedStep: select(store).getSelectedStep(),
      workflowSaved: select(store).getSelectedStep(),
    }),
    [],
  );

  const emailId = selectedStep?.args?.email_id as string | undefined;

  const createEmail = useCallback(async () => {
    setIsSaving(true);
    const response = await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'automation',
        subject: '',
      },
    });

    dispatch(store).updateStepArgs(
      selectedStep.id,
      'email_id',
      parseInt(response.data.id as string, 10),
    );

    dispatch(store).save();
  }, [selectedStep.id]);

  // This component is rendered only when no email ID is set. Once we have the ID
  // and the workflow is saved, we can safely redirect to the email design flow.
  useEffect(() => {
    if (emailId && workflowSaved) {
      window.location.href = `admin.php?page=mailpoet-newsletter-editor&id=${emailId}`;
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
