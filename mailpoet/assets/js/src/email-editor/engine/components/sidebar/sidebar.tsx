import { __ } from '@wordpress/i18n';
import { useContext, useRef, useEffect } from '@wordpress/element';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
  BlockInspector,
  store as blockEditorStore,
} from '@wordpress/block-editor';
import { ComplementaryArea } from '@wordpress/interface';
import { drawerRight } from '@wordpress/icons';
import { store as editorStore } from '@wordpress/editor';
import {
  storeName,
  mainSidebarEmailTab,
  mainSidebarBlockTab,
  mainSidebarId,
} from '../../store';
import { Header } from './header';
import { EmailSettings } from './email-settings';
import { TemplateSettings } from './template-settings';
import { unlock } from '../../../lock-unlock';

import './index.scss';

const { Tabs } = unlock(componentsPrivateApis);

type Props = React.ComponentProps<typeof ComplementaryArea>;

function SidebarContent(props: Props) {
  const { isEditingTemplate } = useSelect(
    (select) => ({
      isEditingTemplate:
        select(editorStore).getCurrentPostType() === 'wp_template',
    }),
    [],
  );

  const tabListRef = useRef(null);
  // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
  const tabsContextValue = useContext(Tabs.Context);

  return (
    <ComplementaryArea
      identifier={mainSidebarId}
      headerClassName="editor-sidebar__panel-tabs"
      className="edit-post-sidebar"
      header={
        <Tabs.Context.Provider value={tabsContextValue}>
          <Header ref={tabListRef} />
        </Tabs.Context.Provider>
      }
      icon={drawerRight}
      scope={storeName}
      smallScreenTitle={__('No title', 'mailpoet')}
      isActiveByDefault
      {...props}
    >
      <Tabs.Context.Provider value={tabsContextValue}>
        <Tabs.TabPanel tabId={mainSidebarEmailTab}>
          {isEditingTemplate ? <TemplateSettings /> : <EmailSettings />}
        </Tabs.TabPanel>
        <Tabs.TabPanel tabId={mainSidebarBlockTab}>
          <BlockInspector />
        </Tabs.TabPanel>
      </Tabs.Context.Provider>
    </ComplementaryArea>
  );
}

export function Sidebar(props: Props) {
  const { toggleSettingsSidebarActiveTab } = useDispatch(storeName);
  const { activeTab, selectedBlockId } = useSelect(
    (select) => ({
      activeTab: select(storeName).getSettingsSidebarActiveTab(),
      selectedBlockId: select(blockEditorStore).getSelectedBlockClientId(),
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
    <Tabs
      selectedTabId={activeTab || mainSidebarEmailTab}
      onSelect={(key) => toggleSettingsSidebarActiveTab(key as string)}
    >
      <SidebarContent {...props} />
    </Tabs>
  );
}
