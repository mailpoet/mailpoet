import { privateApis as componentsPrivateApis } from '@wordpress/components';
import * as React from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { stepSidebarKey, automationSidebarKey } from '../../store';

import { unlock } from '../../../lock-unlock';

const { Tabs } = unlock(componentsPrivateApis);

// See:
//   https://github.com/WordPress/gutenberg/blob/e841c9e52d28ba314a535065f9723ec0bc40342c/packages/editor/src/components/sidebar/header.js

function HeaderTabs(_, ref) {
  return (
    <Tabs.TabList ref={ref}>
      <Tabs.Tab tabId={automationSidebarKey}>
        {__('Automation', 'mailpoet')}
      </Tabs.Tab>
      <Tabs.Tab tabId={stepSidebarKey}>{__('Step', 'mailpoet')}</Tabs.Tab>
    </Tabs.TabList>
  );
}

export const Header = React.forwardRef(HeaderTabs);
