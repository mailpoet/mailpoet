import { Panel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { BlockInspector } from '@wordpress/block-editor';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
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

import './index.scss';

type Props = ComponentProps<typeof ComplementaryArea>;

export function Sidebar(props: Props): JSX.Element {
  const { activeTab, isEditingTemplate } = useSelect(
    (select) => ({
      activeTab: select(storeName).getSettingsSidebarActiveTab(),
      isEditingTemplate:
        select(editorStore).getCurrentPostType() === 'wp_template',
    }),
    [],
  );

  return (
    <ComplementaryArea
      identifier={mainSidebarId}
      headerClassName="edit-post-sidebar__panel-tabs"
      className="edit-post-sidebar"
      header={<Header />}
      icon={drawerRight}
      scope={storeName}
      smallScreenTitle={__('No title', 'mailpoet')}
      isActiveByDefault
      {...props}
    >
      {activeTab === mainSidebarEmailTab && isEditingTemplate && (
        <TemplateSettings />
      )}
      {activeTab === mainSidebarEmailTab && !isEditingTemplate && (
        <EmailSettings />
      )}
      {activeTab === mainSidebarBlockTab && (
        <Panel>
          <BlockInspector />
        </Panel>
      )}
    </ComplementaryArea>
  );
}
