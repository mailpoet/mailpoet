import { useDispatch } from '@wordpress/data';
import { close } from '@wordpress/icons';
import { Button, createSlotFill } from '@wordpress/components';
import { storeName } from '../../store';

const { Slot: ListviewSlot, Fill: ListviewFill } = createSlotFill(
  'EmailEditorListview',
);

export function ListviewSidebar() {
  const { toggleListviewSidebar } = useDispatch(storeName);
  return (
    <div className="edit-post-editor__inserter-panel">
      <div className="edit-post-editor__inserter-panel-header">
        <Button icon={close} onClick={toggleListviewSidebar} />
      </div>
      <div className="edit-post-editor__list-view-panel-content">
        <div className="edit-post-editor__list-view-container">
          <ListviewSlot bubblesVirtually />
        </div>
      </div>
    </div>
  );
}

ListviewSidebar.ListviewFill = ListviewFill;
