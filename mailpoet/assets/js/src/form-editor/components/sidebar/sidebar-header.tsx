import { ReactNode } from 'react';
import { Button } from '@wordpress/components';

type Props = {
  closeSidebar: () => void;
  children: ReactNode;
};

export function SidebarHeader({ children, closeSidebar }: Props): JSX.Element {
  return (
    <div className="components-panel__header interface-complementary-area-header editor-sidebar__panel-tabs">
      {children}
      <Button onClick={closeSidebar} icon="no-alt" />
    </div>
  );
}
