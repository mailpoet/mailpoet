import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '../../../components/button';
import { storeName } from '../../../../../editor/store';
import { MailPoet } from '../../../../../../mailpoet';

const emailPreviewLinkCache = {};
const retrievePreviewLink = async (emailId) => {
  if (
    emailPreviewLinkCache[emailId] &&
    emailPreviewLinkCache[emailId].length > 0
  ) {
    return emailPreviewLinkCache[emailId];
  }
  const response = await MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'newsletters',
    action: 'get',
    data: {
      id: emailId,
    },
  });
  emailPreviewLinkCache[emailId] = response?.meta?.preview_url ?? '';
  return emailPreviewLinkCache[emailId];
};

export function EditNewsletter(): JSX.Element {
  const [redirectToTemplateSelection, setRedirectToTemplateSelection] =
    useState(false);
  const [fetchingPreviewLink, setFetchingPreviewLink] = useState(false);

  const { selectedStep, workflowId, workflowSaved, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      workflowId: select(storeName).getWorkflowData().id,
      workflowSaved: select(storeName).getWorkflowSaved(),
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      ),
    }),
    [],
  );

  const emailId = selectedStep?.args?.email_id as number | undefined;
  const workflowStepId = selectedStep.id;
  const errorFields = errors?.fields ?? {};
  const emailIdError = errorFields?.email_id ?? '';

  const createEmail = useCallback(async () => {
    setRedirectToTemplateSelection(true);
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
    if (redirectToTemplateSelection && emailId && workflowSaved) {
      window.location.href = `admin.php?page=mailpoet-newsletters#/template/${emailId}`;
    }
  }, [emailId, workflowSaved, redirectToTemplateSelection]);

  if (!emailId || redirectToTemplateSelection) {
    return (
      <div className={emailIdError ? 'mailpoet-automation-field__error' : ''}>
        <Button
          variant="sidebar-primary"
          centered
          icon={plus}
          onClick={createEmail}
          isBusy={redirectToTemplateSelection}
          disabled={redirectToTemplateSelection}
        >
          {__('Design email', 'mailpoet')}
        </Button>
        {emailIdError && (
          <span className="mailpoet-automation-field-message">
            {__(
              'You need to design an email before you can activate the workflow',
              'mailpoet',
            )}
          </span>
        )}
      </div>
    );
  }

  return (
    <div className="mailpoet-automation-email-buttons">
      <Button
        variant="sidebar-primary"
        centered
        href={`?page=mailpoet-newsletter-editor&id=${
          selectedStep.args.email_id as string
        }`}
      >
        {__('Edit content', 'mailpoet')}
      </Button>
      <Button
        variant="secondary"
        centered
        isBusy={fetchingPreviewLink}
        disabled={fetchingPreviewLink}
        onClick={async () => {
          setFetchingPreviewLink(true);
          const link = await retrievePreviewLink(emailId);
          window.open(link as string, '_blank');
          setFetchingPreviewLink(false);
        }}
      >
        {__('Preview', 'mailpoet')}
      </Button>
    </div>
  );
}
