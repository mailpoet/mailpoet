import React from 'react';
import { Button } from '@wordpress/components';

type Props = {
  closeSidebar: () => any,
  children: React.ReactNode,
}

const SidebarHeader = ({ children, closeSidebar }: Props) => (
  <>
    <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
      { children }
      <Button
        onClick={closeSidebar}
        icon="no-alt"
      />
    </div>
  </>
);

export default SidebarHeader;
