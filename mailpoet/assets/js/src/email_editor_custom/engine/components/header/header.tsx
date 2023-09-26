import { useRef } from '@wordpress/element';
import { PinnedItems } from '@wordpress/interface';
import { Button, ToolbarItem } from '@wordpress/components';
import { NavigableToolbar } from '@wordpress/block-editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { plus } from '@wordpress/icons';
import { storeName } from '../../store';

export function Header() {
  const inserterButton = useRef();

  const { toggleInserterSidebar } = useDispatch(storeName);
  const { isInserterSidebarOpened } = useSelect(
    (select) => ({
      isInserterSidebarOpened: select(storeName).isInserterSidebarOpened(),
    }),
    [],
  );

  const preventDefault = (event) => {
    event.preventDefault();
  };

  const shortLabel = !isInserterSidebarOpened ? __('Add') : __('Close');

  return (
    <div className="edit-post-header">
      <div className="edit-post-header__toolbar">
        <NavigableToolbar
          className="edit-post-header-toolbar"
          aria-label={__('Email document tools', 'mailpoet')}
        >
          <div className="edit-post-header-toolbar__left">
            <ToolbarItem
              ref={inserterButton}
              as={Button}
              className="edit-post-header-toolbar__inserter-toggle"
              variant="primary"
              isPressed={isInserterSidebarOpened}
              onMouseDown={preventDefault}
              onClick={toggleInserterSidebar}
              disabled={false}
              icon={plus}
              label={shortLabel}
              showTooltip
              aria-expanded={isInserterSidebarOpened}
            />
          </div>
        </NavigableToolbar>
        <div className="edit-post-header__center">Todo Email Name</div>
      </div>
      <div className="edit-post-header__settings">
        <PinnedItems.Slot scope={storeName} />
      </div>
    </div>
  );
}
