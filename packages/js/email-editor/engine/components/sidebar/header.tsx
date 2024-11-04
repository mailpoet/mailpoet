import { __ } from '@wordpress/i18n';
import * as React from '@wordpress/element';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { mainSidebarEmailTab, mainSidebarBlockTab } from '../../store';
import { unlock } from '../../../lock-unlock';

const { Tabs } = unlock(componentsPrivateApis);

export function HeaderTabs(_, ref) {
  return (
    <Tabs.TabList ref={ref}>
      <Tabs.Tab tabId={mainSidebarEmailTab}>{__('Email', 'mailpoet')}</Tabs.Tab>
      <Tabs.Tab tabId={mainSidebarBlockTab}>{__('Block')}</Tabs.Tab>
    </Tabs.TabList>
  );
}

export const Header = React.forwardRef(HeaderTabs);
