import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { MailPoetEmailData, storeName } from 'email-editor/engine/store';
import { useSelect } from '@wordpress/data';
import { useContentValidation } from 'email-editor/engine/hooks';

export function SendButton() {
  const [mailpoetEmail] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const { validateContent, isValid } = useContentValidation();
  const { hasEmptyContent, isEmailSent } = useSelect(
    (select) => ({
      hasEmptyContent: select(storeName).hasEmptyContent(),
      isEmailSent: select(storeName).isEmailSent(),
    }),
    [],
  );

  const mailpoetEmailData: MailPoetEmailData = mailpoetEmail;
  return (
    <Button
      variant="primary"
      onClick={() => {
        if (validateContent()) {
          window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetEmailData.id}`;
        }
      }}
      disabled={hasEmptyContent || isEmailSent || isValid}
    >
      {__('Send', 'mailpoet')}
    </Button>
  );
}
