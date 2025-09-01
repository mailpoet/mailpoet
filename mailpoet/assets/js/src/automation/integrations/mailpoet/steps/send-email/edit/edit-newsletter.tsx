import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { useCallback, useEffect, useState } from 'react';
import { store as noticesStore } from '@wordpress/notices';
import { Button } from '../../../components/button';
import { storeName } from '../../../../../editor/store';
import { MailPoet } from '../../../../../../mailpoet';

type HandleDuplicatedStepType = {
  newEmailId: number;
  newEmailWpPostId?: number;
};

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
  const [isHandlingDuplicatedStep, setIsHandlingDuplicatedStep] =
    useState(false);

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
  const emailWpPostId = selectedStep?.args?.email_wp_post_id as
    | number
    | undefined;
  const automationStepId = selectedStep.id;
  const errorFields = errors?.fields ?? {};
  const emailIdError = errorFields?.email_id ?? '';
  const isDuplicatedStep = selectedStep?.args?.stepDuplicated === true;

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
        new_editor: MailPoet.useBlockEmailEditorForAutomationNewsletter,
      },
    });

    void dispatch(storeName).updateStepArgs(
      automationStepId,
      'email_id',
      parseInt(response.data.id as string, 10),
    );

    if (response?.data?.wp_post_id) {
      void dispatch(storeName).updateStepArgs(
        automationStepId,
        'email_wp_post_id',
        parseInt(response.data.wp_post_id as string, 10),
      );
    }

    void dispatch(storeName).save();
  }, [automationId, automationStepId]);

  const handleDuplicatedStep =
    useCallback(async (): Promise<HandleDuplicatedStepType | null> => {
      try {
        // Save the automation to trigger backend duplication
        const savedData = await dispatch(storeName).save();
        const newSelectedStep = savedData.automation.steps[automationStepId];
        const newEmailId = Number(newSelectedStep?.args?.email_id);

        if (!newEmailId || Number.isNaN(newEmailId)) {
          throw new Error('Failed to retrieve new email ID after duplication');
        }

        const newEmailWpPostId = Number(
          newSelectedStep?.args?.email_wp_post_id,
        );

        const info: HandleDuplicatedStepType = { newEmailId };

        if (newEmailWpPostId && !Number.isNaN(newEmailWpPostId)) {
          info.newEmailWpPostId = newEmailWpPostId;
        }

        return info;
      } catch (error) {
        void dispatch(noticesStore).createErrorNotice(
          __('Email duplication failed. Please try again.', 'mailpoet'),
          { explicitDismiss: true },
        );

        return null;
      }
    }, [automationStepId]);

  const handleEditContent = useCallback(async () => {
    // Ensure we have a valid selected step
    if (!selectedStep?.args?.email_id) {
      return;
    }

    // Ensure email ID is a valid number to prevent injection
    const currentEmailId = Number(selectedStep.args.email_id);
    if (!currentEmailId || Number.isNaN(currentEmailId)) {
      return;
    }

    let newUrl = MailPoet.getNewsletterEditorUrl(currentEmailId, 'automation');

    const currentEmailWpPostId = Number(selectedStep?.args?.email_wp_post_id);

    if (currentEmailWpPostId && !Number.isNaN(currentEmailWpPostId)) {
      newUrl = MailPoet.getBlockEmailEditorUrl(currentEmailWpPostId);
    }

    if (isDuplicatedStep) {
      setIsHandlingDuplicatedStep(true);

      const { newEmailId, newEmailWpPostId } = await handleDuplicatedStep();

      if (newEmailWpPostId) {
        newUrl = MailPoet.getBlockEmailEditorUrl(newEmailWpPostId);
      } else if (newEmailId) {
        newUrl = MailPoet.getNewsletterEditorUrl(newEmailId, 'automation');
      } else {
        // If duplication failed, don't redirect and let user see the error
        setIsHandlingDuplicatedStep(false);
        return;
      }

      setIsHandlingDuplicatedStep(false);
    }

    window.location.href = newUrl;
  }, [
    isDuplicatedStep,
    selectedStep?.args?.email_id,
    selectedStep?.args?.email_wp_post_id,
    handleDuplicatedStep,
  ]);

  // This component is rendered only when no email ID is set. Once we have the ID
  // and the automation is saved, we can safely redirect to the email design flow.
  useEffect(() => {
    if (redirectToTemplateSelection && emailId && savedState === 'saved') {
      if (emailWpPostId) {
        window.location.href = MailPoet.getBlockEmailEditorUrl(emailWpPostId);
      } else {
        window.location.href = `admin.php?page=mailpoet-newsletters&context=automation#/template/${emailId}`;
      }
    }
  }, [emailId, emailWpPostId, savedState, redirectToTemplateSelection]);

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
        onClick={handleEditContent}
        isBusy={isHandlingDuplicatedStep}
        disabled={isHandlingDuplicatedStep}
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
