import {
  __experimentalLibrary as Library,
  store as blockEditorStore,
} from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { storeName } from '../../store';

export function InserterSidebar() {
  const { postContentId, isEditingEmailContent } = useSelect((select) => {
    const blocks = select(blockEditorStore).getBlocks();
    return {
      postContentId: blocks.find((block) => block.name === 'core/post-content')
        ?.clientId,
      isEditingEmailContent:
        select(editorStore).getCurrentPostType() !== 'wp_template',
    };
  });

  const { toggleInserterSidebar } = useDispatch(storeName);

  return (
    <div className="editor-inserter-sidebar">
      <div className="editor-inserter-sidebar__content">
        <Library
          showMostUsedBlocks
          showInserterHelpPanel={false}
          // In the email content mode we insert primarily into the post content block.
          rootClientId={isEditingEmailContent ? postContentId : null}
          onClose={toggleInserterSidebar}
        />
      </div>
    </div>
  );
}
