import { ComponentProps } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { Platform, useRef, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { drawerRight } from '@wordpress/icons';
import {
  ComplementaryArea,
  store as interfaceStore,
} from '@wordpress/interface';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';
import { Header } from './header';
import { StepSidebar } from './step';
import { AutomationSidebar } from './automation';
import { stepSidebarKey, storeName, automationSidebarKey } from '../../store';
import { unlock } from '../../../lock-unlock';

const { Tabs } = unlock(componentsPrivateApis);

// See:
//   https://github.com/WordPress/gutenberg/blob/e841c9e52d28ba314a535065f9723ec0bc40342c/packages/editor/src/components/sidebar/index.js

const sidebarActiveByDefault = Platform.select({
  web: true,
  native: false,
});

type Props = ComponentProps<typeof ComplementaryArea>;

function SidebarContent(props: Props): JSX.Element {
  const { keyboardShortcut, sidebarKey, showIconLabels, automationName } =
    useSelect(
      (select) => ({
        keyboardShortcut: select(
          keyboardShortcutsStore,
        ).getShortcutRepresentation(
          'mailpoet/automation-editor/toggle-sidebar',
        ),
        sidebarKey:
          select(interfaceStore).getActiveComplementaryArea(storeName) ??
          automationSidebarKey,
        showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
        automationName: select(storeName).getAutomationData().name,
      }),
      [],
    );

  const tabListRef = useRef(null);
  // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
  const tabsContextValue = useContext(Tabs.Context);

  return (
    <ComplementaryArea
      identifier={sidebarKey}
      header={
        <Tabs.Context.Provider value={tabsContextValue}>
          <Header ref={tabListRef} />
        </Tabs.Context.Provider>
      }
      closeLabel={__('Close settings', 'mailpoet')}
      headerClassName="editor-sidebar__panel-tabs"
      title={__('Settings', 'mailpoet')}
      icon={drawerRight}
      className="edit-site-sidebar mailpoet-automation-sidebar"
      panelClassName="edit-site-sidebar"
      smallScreenTitle={automationName || __('(no title)', 'mailpoet')}
      scope={storeName}
      toggleShortcut={keyboardShortcut}
      isActiveByDefault={sidebarActiveByDefault}
      showIconLabels={showIconLabels}
      {...props}
    >
      <Tabs.Context.Provider value={tabsContextValue}>
        <Tabs.TabPanel tabId={automationSidebarKey}>
          <AutomationSidebar />
        </Tabs.TabPanel>
        <Tabs.TabPanel tabId={stepSidebarKey}>
          <StepSidebar />
        </Tabs.TabPanel>
      </Tabs.Context.Provider>
    </ComplementaryArea>
  );
}

export function Sidebar(props: Props): JSX.Element {
  const { openSidebar } = useDispatch(storeName);

  const { sidebarKey } = useSelect(
    (select) => ({
      sidebarKey:
        select(interfaceStore).getActiveComplementaryArea(storeName) ??
        automationSidebarKey,
    }),
    [],
  );

  return (
    <Tabs selectedTabId={sidebarKey} onSelect={openSidebar}>
      <SidebarContent {...props} />
    </Tabs>
  );
}
