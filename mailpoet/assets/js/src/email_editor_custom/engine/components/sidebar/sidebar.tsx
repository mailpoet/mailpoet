import { createSlotFill, Panel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
import { storeName, mainSidebarKey } from 'email_editor_custom/engine/store';
import { drawerRight } from '@wordpress/icons';

const { Slot: InspectorSlot, Fill: InspectorFill } = createSlotFill(
  'EmailEditorBlockInspector',
);

type Props = ComponentProps<typeof ComplementaryArea>;

export function Sidebar(props: Props): JSX.Element {
  return (
    <ComplementaryArea
      identifier={mainSidebarKey}
      className="edit-post-sidebar"
      header={<h2>Todo header</h2>}
      icon={drawerRight}
      scope={storeName}
      smallScreenTitle={__('No title', 'mailpoet')}
      isActiveByDefault
      {...props}
    >
      <Panel header={__('Inspector')}>
        <InspectorSlot bubblesVirtually />
      </Panel>
    </ComplementaryArea>
  );
}

Sidebar.InspectorFill = InspectorFill;
