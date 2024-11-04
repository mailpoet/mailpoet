import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
  store as editorStore,
  // @ts-expect-error No types available for useEntitiesSavedStatesIsDirty
  useEntitiesSavedStatesIsDirty,
} from '@wordpress/editor';
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

  const { isDirty } = useEntitiesSavedStatesIsDirty();

  const { validateContent, isValid } = useContentValidation();
  const { hasEmptyContent, isEmailSent, isEditingTemplate } = useSelect(
    (select) => ({
      hasEmptyContent: select(storeName).hasEmptyContent(),
      isEmailSent: select(storeName).isEmailSent(),
      isEditingTemplate:
        select(editorStore).getCurrentPostType() === 'wp_template',
    }),
    [],
  );

  const isDisabled =
    isEditingTemplate || hasEmptyContent || isEmailSent || isValid || isDirty;

  const mailpoetEmailData: MailPoetEmailData = mailpoetEmail;
  return (
    <Button
      variant="primary"
      onClick={() => {
        if (validateContent()) {
          window.location.href = `admin.php?page=mailpoet-newsletters#/send/${mailpoetEmailData.id}`;
        }
      }}
      disabled={isDisabled}
    >
      {__('Send', 'mailpoet')}
    </Button>
  );
}
