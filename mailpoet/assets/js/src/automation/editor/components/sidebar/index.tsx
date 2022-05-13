import { ComponentProps } from 'react';
import { Platform } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cog } from '@wordpress/icons';
import { ComplementaryArea } from '@wordpress/interface';
import { storeName } from '../../store';

// See:
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/sidebar/plugin-sidebar/index.js
//   https://github.com/WordPress/gutenberg/blob/5caeae34b3fb303761e3b9432311b26f4e5ea3a6/packages/edit-post/src/components/sidebar/settings-sidebar/index.js

const sidebarActiveByDefault = Platform.select({
  web: true,
  native: false,
});

type Props = ComponentProps<typeof ComplementaryArea>;

export function Sidebar(props: Props): JSX.Element {
  const workflowName = 'Testing workflow';
  return (
    <ComplementaryArea
      identifier="mailpoet/automation-editor/workflow"
      closeLabel={__('Close settings')}
      headerClassName="edit-post-sidebar__panel-tabs"
      title={__('Settings')}
      icon={cog}
      className="edit-post-sidebar"
      panelClassName="edit-post-sidebar"
      smallScreenTitle={workflowName || __('(no title)')}
      scope={storeName}
      isActiveByDefault={sidebarActiveByDefault}
      {...props}
    >
      Sidebar
    </ComplementaryArea>
  );
}
