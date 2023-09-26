import { useDispatch } from '@wordpress/data';
import { close } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { __experimentalLibrary as Library } from '@wordpress/block-editor';
import { storeName } from '../../store';

export function InserterSidebar() {
  const { toggleInserterSidebar } = useDispatch(storeName);
  return (
    <div className="edit-post-editor__inserter-panel">
      <div className="edit-post-editor__inserter-panel-header">
        <Button icon={close} onClick={toggleInserterSidebar} />
      </div>
      <div className="edit-post-editor__inserter-panel-content">
        <Library
          showMostUsedBlocks
          showInserterHelpPanel={false}
          rootClientId={undefined}
          __experimentalInsertionIndex={undefined}
        />
      </div>
    </div>
  );
}
