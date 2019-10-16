import React from 'react';
import { IconButton } from '@wordpress/components';

export default () => (
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
        onClick={() => null}
        isToggled
      />
    </div>
  </div>
);
