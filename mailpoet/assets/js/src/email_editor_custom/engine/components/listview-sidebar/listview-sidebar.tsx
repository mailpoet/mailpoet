import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { close } from '@wordpress/icons';
import { Button } from '@wordpress/components';
import { __experimentalListView as ListView } from '@wordpress/block-editor';
import { storeName } from '../../store';

export function ListviewSidebar() {
  const { toggleListviewSidebar } = useDispatch(storeName);

  const [dropZoneElement, setDropZoneElement] = useState(null);
  return (
    <div className="edit-post-editor__inserter-panel">
      <div className="edit-post-editor__inserter-panel-header">
        <Button icon={close} onClick={toggleListviewSidebar} />
      </div>
      <div className="edit-post-editor__list-view-panel-content">
        <div
          className="edit-post-editor__list-view-container"
          ref={setDropZoneElement}
        >
          <ListView dropZoneElement={dropZoneElement} />
        </div>
      </div>
    </div>
  );
}
