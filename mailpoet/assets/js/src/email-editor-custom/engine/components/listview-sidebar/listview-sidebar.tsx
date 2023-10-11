import { createSlotFill } from '@wordpress/components';

const { Slot: ListviewSlot, Fill: ListviewFill } = createSlotFill(
  'EmailEditorListview',
);

export function ListviewSidebar() {
  return (
    <div className="edit-post-editor__inserter-panel edit-post-editor__document-overview-panel">
      <div className="edit-post-editor__list-view-panel-content">
        <div className="edit-post-editor__list-view-container">
          <ListviewSlot bubblesVirtually />
        </div>
      </div>
    </div>
  );
}

ListviewSidebar.ListviewFill = ListviewFill;
