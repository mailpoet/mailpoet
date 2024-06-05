import { ReactNode } from 'react';
import { MailPoet } from 'mailpoet';
import { Button } from '@wordpress/components';

type Props = {
  closeSidebar: () => void;
  children: ReactNode;
};

export function SidebarHeader({ children, closeSidebar }: Props): JSX.Element {
  return (
    <>
      <div className="components-panel__header interface-complementary-area-header__small">
        <span className="interface-complementary-area-header__small-title">
          {MailPoet.I18n.t('formSettings')}
        </span>
        <Button onClick={closeSidebar} icon="no-alt" />
      </div>
      <div className="components-panel__header interface-complementary-area-header editor-sidebar__panel-tabs">
        {children}
        <Button onClick={closeSidebar} icon="no-alt" />
      </div>
    </>
  );
}
