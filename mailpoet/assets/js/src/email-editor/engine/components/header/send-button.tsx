import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { MailPoetEmailData, storeName } from 'email-editor/engine/store';
import { useSelect } from '@wordpress/data';

export function SendButton() {
  const [mailpoetEmail] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );
  const { hasEmptyContent } = useSelect(
    (select) => ({
      hasEmptyContent: select(storeName).hasEmptyContent(),
    }),
    [],
  );

  const mailpoetEmailData: MailPoetEmailData = mailpoetEmail;
  return (
    <Button
      variant="primary"
      onClick={() => {
        window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetEmailData.id}`;
      }}
      disabled={hasEmptyContent}
    >
      {__('Send', 'mailpoet')}
    </Button>
  );
}
