import { createSlotFill, Panel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
  ComplementaryArea,
  store as interfaceStore,
} from '@wordpress/interface';
import { ComponentProps } from 'react';
import { drawerRight } from '@wordpress/icons';
import {
  storeName,
  mainSidebarEmailKey,
  mainSidebarBlockKey,
} from '../../store';
import { Header } from './header';
import { EmailSettings } from './email_settings';

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  'EmailEditorBlockInspector',
);

type Props = ComponentProps<typeof ComplementaryArea>;

export function Sidebar(props: Props): JSX.Element {
  const { sidebarKey } = useSelect((select) => ({
    sidebarKey:
      select(interfaceStore).getActiveComplementaryArea(storeName) ??
      mainSidebarEmailKey,
  }));

  return (
    <ComplementaryArea
      identifier={sidebarKey}
      className="edit-post-sidebar"
      header={<Header sidebarKey={sidebarKey} />}
      icon={drawerRight}
      scope={storeName}
      smallScreenTitle={__('No title', 'mailpoet')}
      isActiveByDefault
      {...props}
    >
      {sidebarKey === mainSidebarEmailKey && <EmailSettings />}
      {sidebarKey === mainSidebarBlockKey && (
        <Panel>
          <InspectorSlot bubblesVirtually />
        </Panel>
      )}
    </ComplementaryArea>
  );
}

Sidebar.InspectorFill = InspectorFill;
