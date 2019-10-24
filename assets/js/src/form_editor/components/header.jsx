import React from 'react';
import { IconButton } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

export default () => {
  const sidebarOpened = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarOpened(),
    []
  );
  const { toggleSidebar } = useDispatch('mailpoet-form-editor');
  return (
    <div className="edit-post-header">
      <div className="edit-post-header-toolbar" />
      <div className="edit-post-header__settings">
        <button
          type="button"
          className="components-button editor-post-publish-panel__toggle is-button is-primary"
        >
          Save
        </button>
        <IconButton
          icon="admin-generic"
          label="Settings"
          labelPosition="down"
          onClick={() => toggleSidebar(!sidebarOpened)}
          isToggled={sidebarOpened}
        />
      </div>
    </div>
  );
};
