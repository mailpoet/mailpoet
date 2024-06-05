import { useEffect } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { FormSettings } from 'form-editor/components/form-settings/form-settings';
import { BlockSettings } from './block-settings.jsx';
import { SidebarHeader } from './sidebar-header';
import { storeName } from '../../store';
import { unlock } from '../../lock-unlock';

const { Tabs } = unlock(componentsPrivateApis);

type Props = {
  onClose: () => void;
};
export function DefaultSidebar({ onClose }: Props): JSX.Element {
  const { activeTab, selectedBlockId } = useSelect(
    (select) => ({
      activeTab: select(storeName).getDefaultSidebarActiveTab(),
      selectedBlockId: select('core/block-editor').getSelectedBlockClientId(),
    }),
    [],
  );

  const { switchDefaultSidebarTab } = useDispatch(storeName);

  useEffect(() => {
    if (selectedBlockId) {
      void switchDefaultSidebarTab('block');
    } else {
      void switchDefaultSidebarTab('form');
    }
  }, [selectedBlockId, switchDefaultSidebarTab]);

  return (
    <Tabs selectedTabId={activeTab} onSelect={switchDefaultSidebarTab}>
      <SidebarHeader closeSidebar={onClose}>
        <Tabs.TabList>
          <Tabs.Tab tabId="form">{MailPoet.I18n.t('form')}</Tabs.Tab>
          <Tabs.Tab tabId="block">{__('Block')}</Tabs.Tab>
        </Tabs.TabList>
      </SidebarHeader>
      <Tabs.TabPanel tabId="form">
        <FormSettings />
      </Tabs.TabPanel>
      <Tabs.TabPanel tabId="block">
        <BlockSettings />
      </Tabs.TabPanel>
    </Tabs>
  );
}
