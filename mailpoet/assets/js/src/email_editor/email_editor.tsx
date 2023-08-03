import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
  useSelect,
  subscribe,
  select as directSelect,
  dispatch as directDispatch,
} from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { NextButtonSlot } from './components/next_button_slot';
import { MailPoetEmailData } from './types';

import './email_editor.scss';

// Hack to temporarily disable block patterns
directSelect(coreStore).getBlockPatterns = () => [];
directSelect(coreStore).getBlockPatternCategories = () => [];

function Editor() {
  const { mailpoetData } = useSelect((select) => ({
    mailpoetData:
      (select(editorStore).getEditedPostAttribute(
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        // The getEditedPostAttribute accepts an attribute but typescript thinks it doesn't
        'mailpoet_data',
      ) as MailPoetEmailData) ?? null,
  }));

  return (
    <NextButtonSlot>
      <Button
        variant="primary"
        disabled={!mailpoetData}
        onClick={() => {
          const isPostDirty = directSelect(editorStore).isEditedPostDirty();
          const sendUrl = `admin.php?page=mailpoet-newsletters#/send/${mailpoetData.id}`;
          if (!isPostDirty) {
            window.location.href = sendUrl;
            return;
          }
          directDispatch(editorStore).savePost();
          const unsubscribe = subscribe(() => {
            const isStillDirty = directSelect(editorStore).isEditedPostDirty();
            const isSaving = directSelect(editorStore).isSavingPost();
            const didSave =
              directSelect(editorStore).didPostSaveRequestSucceed();
            if (!isSaving && didSave && !isStillDirty) {
              unsubscribe();
              window.location.href = sendUrl;
            }
          });
        }}
      >
        {__('Next', 'mailpoet')}
      </Button>
    </NextButtonSlot>
  );
}

function initializeEditor() {
  registerPlugin('mailpoet-email-editor', {
    render: Editor,
  });
}

export { initializeEditor };
