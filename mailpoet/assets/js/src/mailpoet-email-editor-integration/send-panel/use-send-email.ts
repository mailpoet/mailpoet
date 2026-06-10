import { useState, useCallback } from '@wordpress/element';
import { select, dispatch, useDispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { store as emailEditorIntegrationStore } from '../store';

type SendResponse = {
  data?: {
    status?: string;
  };
};

type AjaxError = {
  errors?: { message: string }[];
};

function extractErrorMessage(error: AjaxError): string {
  const messages = (error?.errors || []).map((e) => e.message).filter(Boolean);
  return messages.length
    ? messages.join(' ')
    : __('An error occurred while sending the email.', 'mailpoet');
}

type UseSendEmail = {
  sendEmail: () => Promise<void>;
  isSending: boolean;
  error: string | null;
  clearError: () => void;
};

/**
 * Persists the email's pending edits (segments, schedule, subject…) to the
 * newsletter and then triggers the actual send/schedule through MailPoet's
 * sending queue. On success the user is redirected to the email listing; on
 * failure the API error is surfaced for display in the send panel.
 */
export function useSendEmail(): UseSendEmail {
  const [isSending, setIsSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const { closeSendPanel } = useDispatch(emailEditorIntegrationStore);

  const clearError = useCallback(() => setError(null), []);

  const sendEmail = useCallback(async () => {
    const postId = select(editorStore).getCurrentPostId();
    if (!postId) {
      return;
    }

    setIsSending(true);
    setError(null);

    try {
      // Persist pending edits so segments/schedule are stored on the newsletter
      // before the sending queue reads them.
      await dispatch(coreDataStore).saveEditedEntityRecord(
        'postType',
        'mailpoet_email',
        postId,
      );

      const editedPost = select(coreDataStore).getEditedEntityRecord(
        'postType',
        'mailpoet_email',
        postId,
      );
      // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
      const newsletterId = editedPost?.mailpoet_data?.id;

      if (!newsletterId) {
        setError(__('An error occurred while sending the email.', 'mailpoet'));
        return;
      }

      await new Promise<SendResponse>((resolve, reject) => {
        void MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'sendingQueue',
          action: 'add',
          data: {
            newsletter_id: newsletterId,
          },
        })
          .done((response: SendResponse) => resolve(response))
          .fail((err: AjaxError) => reject(err));
      });

      void closeSendPanel();
      window.location.href = window.WooCommerceEmailEditor.urls.listings;
    } catch (err) {
      setError(extractErrorMessage(err as AjaxError));
    } finally {
      setIsSending(false);
    }
  }, [closeSendPanel]);

  return { sendEmail, isSending, error, clearError };
}
