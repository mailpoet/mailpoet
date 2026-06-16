import { useEffect } from '@wordpress/element';
import { select, useSelect, useDispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { storeName as emailEditorStore } from '@woocommerce/email-editor';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { duplicateNewsletter } from '../../newsletters/api';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

const NOTICE_ID = 'mailpoet-email-already-sent';
const NOTICE_CONTEXT = 'email-editor';

export function SentEmailNotice(): null {
  const { isEmailSent, postType } = useSelect(
    (rootSelect) => ({
      isEmailSent: rootSelect(emailEditorStore).isEmailSent(),
      postType: rootSelect(editorStore).getCurrentPostType(),
    }),
    [],
  );
  const { createNotice, removeNotice, createErrorNotice } =
    useDispatch(noticesStore);

  useEffect(() => {
    // Only render the notice a MailPoet email that has already been sent — not for templates or other post types.
    if (postType !== MAILPOET_EMAIL_POST_TYPE || !isEmailSent) {
      return undefined;
    }

    // Duplicate the saved newsletter and open the copy.
    const duplicateAndEdit = async (): Promise<void> => {
      const postId = select(editorStore).getCurrentPostId();
      const editedPost = select(coreDataStore).getEditedEntityRecord(
        'postType',
        MAILPOET_EMAIL_POST_TYPE,
        postId,
      );
      // @ts-expect-error Property 'mailpoet_data' does not exist edited entry record type.
      const newsletterId = editedPost?.mailpoet_data?.id as number | undefined;

      if (!newsletterId) {
        return;
      }

      try {
        const response = await duplicateNewsletter(newsletterId);
        window.location.href = MailPoet.getActiveEmailEditorUrl(response.data);
      } catch (e) {
        void createErrorNotice(
          __(
            'The email could not be duplicated. Please try again.',
            'mailpoet',
          ),
          { type: 'snackbar' },
        );
      }
    };

    void createNotice(
      'warning',
      __(
        'This email has already been sent. It can be edited, but not sent again. Duplicate this email if you want to send it again.',
        'mailpoet',
      ),
      {
        id: NOTICE_ID,
        isDismissible: false,
        context: NOTICE_CONTEXT,
        actions: [
          {
            label: __('Duplicate', 'mailpoet'),
            onClick: () => {
              void duplicateAndEdit();
            },
          },
        ],
      },
    );

    return () => {
      void removeNotice(NOTICE_ID, NOTICE_CONTEXT);
    };
  }, [isEmailSent, postType, createNotice, removeNotice, createErrorNotice]);

  return null;
}
