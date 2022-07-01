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
import { WorkflowSidebar } from './workflow';
import {
  stepSidebarKey,
  store,
  storeName,
  workflowSidebarKey,
} from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/sidebar/plugin-sidebar/index.js
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/sidebar/settings-sidebar/index.js

const sidebarActiveByDefault = Platform.select({
  web: true,
  native: false,
});

type Props = ComponentProps<typeof ComplementaryArea>;

export function Sidebar(props: Props): JSX.Element {
  const { keyboardShortcut, sidebarKey, showIconLabels, workflowName } =
    useSelect(
      (select) => ({
        keyboardShortcut: select(
          keyboardShortcutsStore,
        ).getShortcutRepresentation(
          'mailpoet/automation-editor/toggle-sidebar',
        ),
        sidebarKey:
          select(interfaceStore).getActiveComplementaryArea(storeName) ??
          workflowSidebarKey,
        showIconLabels: select(store).isFeatureActive('showIconLabels'),
        workflowName: select(store).getWorkflowData().name,
      }),
      [],
    );

  return (
    <ComplementaryArea
      identifier={sidebarKey}
      header={<Header sidebarKey={sidebarKey} />}
      closeLabel={__('Close settings')}
      headerClassName="edit-site-sidebar__panel-tabs"
      title={__('Settings')}
      icon={cog}
      className="edit-site-sidebar"
      panelClassName="edit-site-sidebar"
      smallScreenTitle={workflowName || __('(no title)')}
      scope={storeName}
      toggleShortcut={keyboardShortcut}
      isActiveByDefault={sidebarActiveByDefault}
      showIconLabels={showIconLabels}
      {...props}
    >
      {sidebarKey === workflowSidebarKey && <WorkflowSidebar />}
      {sidebarKey === stepSidebarKey && <StepSidebar />}
    </ComplementaryArea>
  );
}
