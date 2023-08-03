import {
  dispatch as directDispatch,
  select as directSelect,
  subscribe,
} from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

type NextButtonProps = {
  newsletterId: number | null;
};

export function NextButton({ newsletterId }: NextButtonProps) {
  return (
    <Button
      variant="primary"
      disabled={!newsletterId}
      onClick={() => {
        const isPostDirty = directSelect(editorStore).isEditedPostDirty();
        const sendUrl = `admin.php?page=mailpoet-newsletters#/send/${newsletterId}`;
        if (!isPostDirty) {
          window.location.href = sendUrl;
          return;
        }
        directDispatch(editorStore).savePost();
        const unsubscribe = subscribe(() => {
          const isStillDirty = directSelect(editorStore).isEditedPostDirty();
          const isSaving = directSelect(editorStore).isSavingPost();
          const didSave = directSelect(editorStore).didPostSaveRequestSucceed();
          if (!isSaving && didSave && !isStillDirty) {
            unsubscribe();
            window.location.href = sendUrl;
          }
        });
      }}
    >
      {__('Next', 'mailpoet')}
    </Button>
  );
}
