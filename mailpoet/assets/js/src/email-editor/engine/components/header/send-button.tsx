import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { MailPoetEmailData, storeName } from 'email-editor/engine/store';
import { useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { validateContent } from '../validation';

export function SendButton() {
  const [mailpoetEmail] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );
  const { hasEmptyContent, isEmailSent, hasValidationError, editedContent } =
    useSelect(
      (select) => ({
        hasEmptyContent: select(storeName).hasEmptyContent(),
        isEmailSent: select(storeName).isEmailSent(),
        hasValidationError:
          select(noticesStore).getNotices('validation').length > 0,
        editedContent: select(storeName).getEditedEmailContent(),
      }),
      [],
    );

  const mailpoetEmailData: MailPoetEmailData = mailpoetEmail;
  return (
    <Button
      variant="primary"
      onClick={() => {
        const result = validateContent(editedContent);

        if (!result) {
          return false;
        }
        window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetEmailData.id}`;
        return true;
      }}
      disabled={hasEmptyContent || isEmailSent || hasValidationError}
    >
      {__('Send', 'mailpoet')}
    </Button>
  );
}
