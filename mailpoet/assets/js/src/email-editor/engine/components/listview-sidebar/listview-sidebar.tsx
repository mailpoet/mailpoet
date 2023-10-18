import { __experimentalListView as ListView } from '@wordpress/block-editor';

export function ListviewSidebar() {
  return (
    <div className="edit-post-editor__inserter-panel edit-post-editor__document-overview-panel">
      <div className="edit-post-editor__list-view-panel-content">
        <div className="edit-post-editor__list-view-container">
          <ListView />
        </div>
      </div>
    </div>
  );
}
