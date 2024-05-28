import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
  mainSidebarBlockTab,
  mainSidebarEmailTab,
  storeName,
} from '../../store';

export function Header() {
  const { toggleSettingsSidebarActiveTab } = useDispatch(storeName);

  const { selectedBlockId, activeTab, isEditingTemplate } = useSelect(
    (select) => ({
      selectedBlockId: select(blockEditorStore).getSelectedBlockClientId(),
      activeTab: select(storeName).getSettingsSidebarActiveTab(),
      isEditingTemplate:
        // @ts-expect-error No types for this exist yet.
        select(editorStore).getCurrentPostType() === 'wp_template',
    }),
    [],
  );

  // Switch tab based on selected block.
  useEffect(() => {
    if (selectedBlockId) {
      void toggleSettingsSidebarActiveTab(mainSidebarBlockTab);
    } else {
      void toggleSettingsSidebarActiveTab(mainSidebarEmailTab);
    }
  }, [selectedBlockId, toggleSettingsSidebarActiveTab]);

  return (
    <ul>
      <li>
        <button
          onClick={() => {
            void toggleSettingsSidebarActiveTab(mainSidebarEmailTab);
          }}
          className={classnames(
            'components-button edit-post-sidebar__panel-tab',
            { 'is-active': activeTab === mainSidebarEmailTab },
          )}
          data-automation-id="email_settings_tab"
          type="button"
        >
          {isEditingTemplate
            ? __('Template', 'mailpoet')
            : __('Email', 'mailpoet')}
        </button>
      </li>
      <li>
        <button
          onClick={() => {
            void toggleSettingsSidebarActiveTab(mainSidebarBlockTab);
          }}
          className={classnames(
            'components-button edit-post-sidebar__panel-tab',
            { 'is-active': activeTab === mainSidebarBlockTab },
          )}
          data-automation-id="mailpoet_block_settings_tab"
          type="button"
        >
          {__('Block', 'mailpoet')}
        </button>
      </li>
    </ul>
  );
}
