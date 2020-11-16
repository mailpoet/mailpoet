import React from 'react';
import MailPoet from 'mailpoet';
import { Button } from '@wordpress/components';

type Props = {
  closeSidebar: () => any;
  children: React.ReactNode;
}

const SidebarHeader = ({ children, closeSidebar }: Props) => (
  <>
    <div className="components-panel__header interface-complementary-area-header__small">
      <span className="interface-complementary-area-header__small-title">{MailPoet.I18n.t('formSettings')}</span>
      <Button
        onClick={closeSidebar}
        icon="no-alt"
      />
    </div>
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
