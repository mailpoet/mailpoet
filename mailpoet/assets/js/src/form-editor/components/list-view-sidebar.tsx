import { __experimentalListView as ListView } from '@wordpress/block-editor';

export function ListviewSidebar() {
  return (
    <div className="editor-list-view-sidebar">
      <div className="editor-list-view-sidebar__list-view-container">
        <div className="editor-list-view-sidebar__list-view-panel-content">
          <div className="edit-post-editor__list-view-container">
            <ListView />
          </div>
        </div>
      </div>
    </div>
  );
}
