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

  const { selectedStep, automationId, savedState, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      automationId: select(storeName).getAutomationData().id,
      savedState: select(storeName).getSavedState(),
      errors: select(storeName).getStepError(
        select(storeName).getSelectedStep().id,
      ),
    }),
    [],
  );

  const emailId = selectedStep?.args?.email_id as number | undefined;
  const automationStepId = selectedStep.id;
  const errorFields = errors?.fields ?? {};
  const emailIdError = errorFields?.email_id ?? '';

  const createEmail = useCallback(async () => {
    setRedirectToTemplateSelection(true);
    const options = {
      automationId,
      automationStepId,
    };
    const response = await MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'automation',
        subject: '',
        options,
      },
    });

    void dispatch(storeName).updateStepArgs(
      automationStepId,
      'email_id',
      parseInt(response.data.id as string, 10),
    );

    void dispatch(storeName).save();
  }, [automationId, automationStepId]);

  // This component is rendered only when no email ID is set. Once we have the ID
  // and the automation is saved, we can safely redirect to the email design flow.
  useEffect(() => {
    if (redirectToTemplateSelection && emailId && savedState === 'saved') {
      window.location.href = `admin.php?page=mailpoet-newsletters&context=automation#/template/${emailId}`;
    }
  }, [emailId, savedState, redirectToTemplateSelection]);

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
              'You need to design an email before you can activate the automation',
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
        }&context=automation`}
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
