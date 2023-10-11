import { createSlotFill } from '@wordpress/components';

const { Slot: InserterSlot, Fill: InserterFill } = createSlotFill(
  'EmailEditorInserter',
);

export function InserterSidebar() {
  return (
    <div className="edit-post-editor__inserter-panel">
      <div className="edit-post-editor__inserter-panel-content">
        <InserterSlot bubblesVirtually />
      </div>
    </div>
  );
}

InserterSidebar.InserterFill = InserterFill;
