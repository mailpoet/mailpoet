import { store as blockEditorStore } from '@wordpress/block-editor';
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

  const selectedBlockId = useSelect(
    (select) => select(blockEditorStore).getSelectedBlockClientId(),
    [],
  );

  const activeTab = useSelect(
    (select) => select(storeName).getSettingsSidebarActiveTab(),
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
    <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
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
            {__('Email', 'mailpoet')}
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
            {__('Block')}
          </button>
        </li>
      </ul>
    </div>
  );
}
