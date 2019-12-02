import React, { useEffect } from 'react';
import { IconButton } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import MailPoet from 'mailpoet';
import BlockSettings from './block_settings.jsx';
import FormSettings from './form_settings/form_settings.jsx';

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
  const activeTab = useSelect(
    (select) => select('mailpoet-form-editor').getSidebarActiveTab(),
    []
  );

  const selectedBlockId = useSelect(
    (select) => select('core/block-editor').getSelectedBlockClientId(),
    []
  );

  const { toggleSidebar, switchSidebarTab } = useDispatch('mailpoet-form-editor');

  useEffect(() => {
    if (selectedBlockId) {
      switchSidebarTab('block');
    } else {
      switchSidebarTab('form');
    }
  }, [selectedBlockId, switchSidebarTab]);

  return (
    <div className="edit-post-sidebar">
      <SidebarHeader closeSidebar={() => toggleSidebar(false)}>
        <ul>
          <li>
            <button
              onClick={() => switchSidebarTab('form')}
              className={classnames('edit-post-sidebar__panel-tab', { 'is-active': activeTab === 'form' })}
              type="button"
            >
              {MailPoet.I18n.t('form')}
            </button>
          </li>
          <li>
            <button
              onClick={() => switchSidebarTab('block')}
              className={classnames('edit-post-sidebar__panel-tab', { 'is-active': activeTab === 'block' })}
              type="button"
            >
              {__('Block')}
            </button>
          </li>
        </ul>
      </SidebarHeader>
      {activeTab === 'form' ? <FormSettings /> : <BlockSettings />}
    </div>
  );
};
