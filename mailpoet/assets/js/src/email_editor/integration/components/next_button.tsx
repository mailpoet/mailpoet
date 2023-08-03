import {
  dispatch as directDispatch,
  select as directSelect,
  subscribe,
} from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';

type NextButtonProps = {
  newsletterId: number | null;
};

export function NextButton({ newsletterId }: NextButtonProps) {
  const [isBusy, setIsBusy] = useState(false);
  return (
    <Button
      variant="primary"
      disabled={!newsletterId}
      isBusy={isBusy}
      onClick={() => {
        setIsBusy(true);
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
          const didFail = directSelect(editorStore).didPostSaveRequestFail();
          if (!isSaving && didSave && !isStillDirty) {
            unsubscribe();
            window.location.href = sendUrl;
          }
          if (!isSaving && didFail) {
            setIsBusy(true);
          }
        });
      }}
    >
      {__('Next', 'mailpoet')}
    </Button>
  );
}
