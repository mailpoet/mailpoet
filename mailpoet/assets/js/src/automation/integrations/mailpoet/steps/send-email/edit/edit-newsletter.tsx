import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import classnames from 'classnames';
import { useCallback, useId, useState } from 'react';
import { store as noticesStore } from '@wordpress/notices';
import { Button } from '../../../components/button';
import { useSelectContext } from '../../../context';
import { storeName } from '../../../../../editor/store';
import { MailPoet } from '../../../../../../mailpoet';
import { sendTelemetryEvent } from '../../../../../editor/telemetry';
import type {
  CreatedAutomationEmail,
  EditorChoice,
  SavedAutomationResult,
} from './email-editor-choice';
import {
  editorChoiceButtonLabels,
  getCreatedAutomationEmail,
  isCreatedEmailPersisted,
  parsePositiveInteger,
} from './email-editor-choice';

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

const getEmailIdErrorMessage = (emailIdError: unknown): string =>
  typeof emailIdError === 'string' && emailIdError.length > 0
    ? emailIdError
    : __(
        'You need to design an email before you can activate the automation',
        'mailpoet',
      );

function EmailIdValidationMessage({
  message,
}: {
  message: string;
}): JSX.Element {
  return (
    <span className="mailpoet-automation-field-message" role="alert">
      {message}
    </span>
  );
}

export function EditNewsletter(): JSX.Element {
  const [creatingEditor, setCreatingEditor] = useState<EditorChoice | null>(
    null,
  );
  const [fetchingPreviewLink, setFetchingPreviewLink] = useState(false);
  const [isHandlingDuplicatedStep, setIsHandlingDuplicatedStep] =
    useState(false);
  const blockEmailEditorHelpId = useId();
  const { block_email_editor_enabled: blockEmailEditorEnabled = false } =
    useSelectContext();

  const { selectedStep, automationId, errors } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
      automationId: select(storeName).getAutomationData().id,
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
  const isDuplicatedStep = selectedStep?.args?.stepDuplicated === true;
  const isBlockEmailEditorEnabled = blockEmailEditorEnabled === true;
  const hasEmailIdError = !!emailIdError;
  const emailIdErrorMessage = getEmailIdErrorMessage(emailIdError);

  const showEditorChoiceError = useCallback(
    (editorChoice: EditorChoice, message: string) => {
      sendTelemetryEvent('button_error', {
        button_label: editorChoiceButtonLabels[editorChoice],
        automation_id: automationId,
      });
      void dispatch(noticesStore).createErrorNotice(message, {
        explicitDismiss: true,
      });
    },
    [automationId],
  );

  const cleanupCreatedEmail = useCallback(
    async (emailIdToDelete: number, editorChoice: EditorChoice) => {
      try {
        await MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'delete',
          data: {
            id: emailIdToDelete,
          },
        });
      } catch {
        showEditorChoiceError(
          editorChoice,
          __(
            'MailPoet couldn’t clean up the email that was not connected. Please try again.',
            'mailpoet',
          ),
        );
      }
    },
    [showEditorChoiceError],
  );

  const redirectToEmailEditor = useCallback(
    (createdEmail: CreatedAutomationEmail, editorChoice: EditorChoice) => {
      if (editorChoice === 'new' && createdEmail.emailWpPostId) {
        if (window.parent && window.parent !== window) {
          window.parent.postMessage(
            {
              type: 'mailpoet-navigate-to-email-editor',
              postId: createdEmail.emailWpPostId,
            },
            window.location.origin,
          );
          return;
        }
        window.location.href = MailPoet.getBlockEmailEditorUrl(
          createdEmail.emailWpPostId,
        );
        return;
      }

      window.location.href = `admin.php?page=mailpoet-newsletters&context=automation#/template/${createdEmail.emailId}`;
    },
    [],
  );

  const createEmail = useCallback(
    async (editorChoice: EditorChoice) => {
      if (creatingEditor) {
        return;
      }

      if (editorChoice === 'new' && !isBlockEmailEditorEnabled) {
        return;
      }

      sendTelemetryEvent('button_click', {
        button_label: editorChoiceButtonLabels[editorChoice],
        automation_id: automationId,
      });

      setCreatingEditor(editorChoice);

      const previousEmailId = selectedStep?.args?.email_id;
      const previousEmailWpPostId = selectedStep?.args?.email_wp_post_id;
      const rollbackStepArgs = () => {
        void dispatch(storeName).updateStepArgs(
          automationStepId,
          'email_id',
          previousEmailId,
        );
        void dispatch(storeName).updateStepArgs(
          automationStepId,
          'email_wp_post_id',
          previousEmailWpPostId,
        );
      };
      let createdEmail: CreatedAutomationEmail | undefined;
      let stagedStepArgs = false;
      let redirected = false;

      try {
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
            new_editor: editorChoice === 'new',
          },
        });

        createdEmail = getCreatedAutomationEmail(response, editorChoice);
        if (!createdEmail) {
          const emailIdFromResponse = parsePositiveInteger(response?.data?.id);
          if (emailIdFromResponse) {
            await cleanupCreatedEmail(emailIdFromResponse, editorChoice);
          }
          showEditorChoiceError(
            editorChoice,
            editorChoice === 'new'
              ? __(
                  'MailPoet couldn’t open the new editor because the email post was not created. Please try again.',
                  'mailpoet',
                )
              : __(
                  'MailPoet couldn’t create the email. Please try again.',
                  'mailpoet',
                ),
          );
          return;
        }

        void dispatch(storeName).updateStepArgs(
          automationStepId,
          'email_id',
          createdEmail.emailId,
        );

        void dispatch(storeName).updateStepArgs(
          automationStepId,
          'email_wp_post_id',
          editorChoice === 'new' ? createdEmail.emailWpPostId : undefined,
        );
        stagedStepArgs = true;

        const saveResult = (await dispatch(
          storeName,
        ).save()) as SavedAutomationResult;
        if (
          !isCreatedEmailPersisted(
            saveResult,
            automationStepId,
            createdEmail,
            editorChoice,
          )
        ) {
          rollbackStepArgs();
          showEditorChoiceError(
            editorChoice,
            __(
              'Email design setup couldn’t be saved. Please try again.',
              'mailpoet',
            ),
          );
          return;
        }

        redirectToEmailEditor(createdEmail, editorChoice);
        redirected = true;
      } catch {
        if (stagedStepArgs) {
          rollbackStepArgs();
        }
        showEditorChoiceError(
          editorChoice,
          stagedStepArgs
            ? __(
                'Email design setup couldn’t be saved. Please try again.',
                'mailpoet',
              )
            : __(
                'MailPoet couldn’t create the email. Please try again.',
                'mailpoet',
              ),
        );
      } finally {
        if (!redirected) {
          setCreatingEditor(null);
        }
      }
    },
    [
      automationId,
      automationStepId,
      cleanupCreatedEmail,
      creatingEditor,
      isBlockEmailEditorEnabled,
      redirectToEmailEditor,
      selectedStep?.args?.email_id,
      selectedStep?.args?.email_wp_post_id,
      showEditorChoiceError,
    ],
  );

  const retrievePreviewLinkForEmail = useCallback(async () => {
    setFetchingPreviewLink(true);
    const link = await retrievePreviewLink(emailId);
    window.open(link as string, '_blank');
    setFetchingPreviewLink(false);
  }, [emailId]);

  const handleDuplicatedStep =
    useCallback(async (): Promise<HandleDuplicatedStepType | null> => {
      try {
        // Save the automation to trigger backend duplication
        const savedData = await dispatch(storeName).save();
        if (savedData?.saved !== true) {
          throw new Error('Automation save was not confirmed');
        }

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
      } catch {
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
    let postIdForNextAdmin = currentEmailWpPostId;

    if (currentEmailWpPostId && !Number.isNaN(currentEmailWpPostId)) {
      newUrl = MailPoet.getBlockEmailEditorUrl(currentEmailWpPostId);
    }

    if (isDuplicatedStep) {
      setIsHandlingDuplicatedStep(true);

      const info = await handleDuplicatedStep();
      if (!info) {
        setIsHandlingDuplicatedStep(false);
        return;
      }

      const { newEmailId, newEmailWpPostId } = info;

      if (newEmailWpPostId) {
        newUrl = MailPoet.getBlockEmailEditorUrl(newEmailWpPostId);
        postIdForNextAdmin = newEmailWpPostId;
      } else if (newEmailId) {
        newUrl = MailPoet.getNewsletterEditorUrl(newEmailId, 'automation');
        postIdForNextAdmin = undefined;
      } else {
        // If duplication failed, don't redirect and let user see the error
        setIsHandlingDuplicatedStep(false);
        return;
      }

      setIsHandlingDuplicatedStep(false);
    }

    // Check if we're in an iframe and navigating to block email editor
    if (
      window.parent &&
      window.parent !== window &&
      postIdForNextAdmin &&
      !Number.isNaN(postIdForNextAdmin)
    ) {
      window.parent.postMessage(
        {
          type: 'mailpoet-navigate-to-email-editor',
          postId: postIdForNextAdmin,
        },
        window.location.origin,
      );
      return;
    }

    window.location.href = newUrl;
  }, [
    isDuplicatedStep,
    selectedStep?.args?.email_id,
    selectedStep?.args?.email_wp_post_id,
    handleDuplicatedStep,
  ]);

  if (!emailId || creatingEditor) {
    return (
      <div
        className={classnames('mailpoet-automation-email-design-options', {
          'mailpoet-automation-field__error': hasEmailIdError,
        })}
      >
        <Button
          variant="sidebar-primary"
          centered
          icon={plus}
          onClick={() => void createEmail('new')}
          isBusy={creatingEditor === 'new'}
          disabled={creatingEditor !== null || !isBlockEmailEditorEnabled}
          aria-describedby={
            isBlockEmailEditorEnabled ? undefined : blockEmailEditorHelpId
          }
          data-automation-id="automation_send_email_design_new_editor"
        >
          {__('Design with the new editor', 'mailpoet')}
        </Button>
        {!isBlockEmailEditorEnabled && (
          <span
            id={blockEmailEditorHelpId}
            className="mailpoet-automation-email-design-help"
          >
            {__(
              'The new editor is unavailable because required dependencies are missing. You can design this email with the classic editor.',
              'mailpoet',
            )}
          </span>
        )}
        <Button
          variant="secondary"
          centered
          icon={plus}
          onClick={() => void createEmail('classic')}
          isBusy={creatingEditor === 'classic'}
          disabled={creatingEditor !== null}
          data-automation-id="automation_send_email_design_classic_editor"
        >
          {__('Design with the classic editor', 'mailpoet')}
        </Button>
        {hasEmailIdError && (
          <EmailIdValidationMessage message={emailIdErrorMessage} />
        )}
      </div>
    );
  }

  return (
    <div
      className={classnames({
        'mailpoet-automation-field__error': hasEmailIdError,
      })}
    >
      <div className="mailpoet-automation-email-buttons">
        <Button
          variant="sidebar-primary"
          centered
          onClick={() => {
            sendTelemetryEvent('button_click', {
              button_label: 'edit_content',
              automation_id: automationId,
            });
            void handleEditContent();
          }}
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
          onClick={() => void retrievePreviewLinkForEmail()}
        >
          {__('Preview', 'mailpoet')}
        </Button>
      </div>
      {hasEmailIdError && (
        <EmailIdValidationMessage message={emailIdErrorMessage} />
      )}
    </div>
  );
}
