import { PinnedItems } from '@wordpress/interface';
import { storeName } from '../../store';

export function Header() {
  return (
    <div className="edit-post-header">
      <div className="edit-post-header__toolbar">
        <div className="edit-post-header-toolbar">Todo Inserter etc.</div>
        <div className="edit-post-header__center">Todo Email Name</div>
      </div>
      <div className="edit-post-header__settings">
        <PinnedItems.Slot scope={storeName} />
      </div>
    </div>
  );
}
