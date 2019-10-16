import React, { useState } from 'react';
import { IconButton } from '@wordpress/components';
import PropTypes from 'prop-types';
import BlockSettings from './block_settings.jsx';
import FormSettings from './form_settings.jsx';

const SidebarHeader = ({ children, closeSidebar }) => (
  <>
    <div className="components-panel__header edit-post-sidebar-header edit-post-sidebar__panel-tabs">
      { children }
      <IconButton
        onClick={closeSidebar}
        icon="no-alt"
      />
    </div>
  </>
);

SidebarHeader.propTypes = {
  closeSidebar: PropTypes.func.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

export default () => {
  const [activeTab, setActiveTab] = useState('form');
  return (
    <div className="edit-post-sidebar">
      <SidebarHeader>
        <ul>
          <li>
            <button
              onClick={() => setActiveTab('form')}
              className={`edit-post-sidebar__panel-tab ${activeTab === 'form' ? 'is-active' : ''}`}
              type="button"
            >
              Form
            </button>
          </li>
          <li>
            <button
              onClick={() => setActiveTab('block')}
              className={`edit-post-sidebar__panel-tab ${activeTab === 'block' ? 'is-active' : ''}`}
              type="button"
            >
              Block
            </button>
          </li>
        </ul>
      </SidebarHeader>
      {activeTab === 'form' ? <FormSettings /> : <BlockSettings />}
    </div>
  );
};
