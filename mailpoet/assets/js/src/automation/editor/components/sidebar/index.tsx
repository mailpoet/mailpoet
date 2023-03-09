import { ComponentProps } from 'react';
import { useSelect } from '@wordpress/data';
import { Platform } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cog } from '@wordpress/icons';
import {
  ComplementaryArea,
  store as interfaceStore,
} from '@wordpress/interface';
import { store as keyboardShortcutsStore } from '@wordpress/keyboard-shortcuts';
import { Header } from './header';
import { StepSidebar } from './step';
import { AutomationSidebar } from './automation';
import { stepSidebarKey, storeName, automationSidebarKey } from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/sidebar/plugin-sidebar/index.js
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/sidebar/settings-sidebar/index.js
//   https://github.com/WordPress/gutenberg/blob/0ee78b1bbe9c6f3e6df99f3b967132fa12bef77d/packages/edit-site/src/components/sidebar/index.js

const sidebarActiveByDefault = Platform.select({
  web: true,
  native: false,
});

type Props = ComponentProps<typeof ComplementaryArea>;

export function Sidebar(props: Props): JSX.Element {
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

  return (
    <ComplementaryArea
      identifier={sidebarKey}
      header={<Header sidebarKey={sidebarKey} />}
      closeLabel={__('Close settings', 'mailpoet')}
      headerClassName="edit-site-sidebar-edit-mode__panel-tabs"
      title={__('Settings', 'mailpoet')}
      icon={cog}
      className="edit-site-sidebar mailpoet-automation-sidebar"
      panelClassName="edit-site-sidebar"
      smallScreenTitle={automationName || __('(no title)', 'mailpoet')}
      scope={storeName}
      toggleShortcut={keyboardShortcut}
      isActiveByDefault={sidebarActiveByDefault}
      showIconLabels={showIconLabels}
      {...props}
    >
      {sidebarKey === automationSidebarKey && <AutomationSidebar />}
      {sidebarKey === stepSidebarKey && <StepSidebar />}
    </ComplementaryArea>
  );
}
