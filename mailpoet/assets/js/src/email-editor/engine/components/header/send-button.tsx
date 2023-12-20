import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { MailPoetEmailData } from 'email-editor/engine/store';

export function SendButton() {
  const [mailpoetEmail] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );
  const mailpoetEmailData: MailPoetEmailData = mailpoetEmail;
  return (
    <Button
      variant="primary"
      onClick={() => {
        window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetEmailData.id}`;
      }}
    >
      {__('Send', 'mailpoet')}
    </Button>
  );
}
