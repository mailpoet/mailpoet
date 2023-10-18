import { createSlotFill } from '@wordpress/components';

const { Slot: InserterSlot, Fill: InserterFill } = createSlotFill(
  'EmailEditorInserter',
);

export function InserterSidebar() {
  return (
    <div className="edit-post-editor__inserter-panel ">
      <InserterSlot
        bubblesVirtually
        className="edit-post-editor__inserter-panel-content"
      />
    </div>
  );
}

InserterSidebar.InserterFill = InserterFill;
