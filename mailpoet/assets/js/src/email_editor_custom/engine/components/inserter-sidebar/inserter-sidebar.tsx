import { useDispatch } from '@wordpress/data';
import { close } from '@wordpress/icons';
import { Button, createSlotFill } from '@wordpress/components';
import { storeName } from '../../store';

const { Slot: InserterSlot, Fill: InserterFill } = createSlotFill(
  'EmailEditorInserter',
);

export function InserterSidebar() {
  const { toggleInserterSidebar } = useDispatch(storeName);
  return (
    <div className="edit-post-editor__inserter-panel">
      <div className="edit-post-editor__inserter-panel-header">
        <Button icon={close} onClick={toggleInserterSidebar} />
      </div>
      <div className="edit-post-editor__inserter-panel-content">
        <InserterSlot bubblesVirtually />
      </div>
    </div>
  );
}

InserterSidebar.InserterFill = InserterFill;
