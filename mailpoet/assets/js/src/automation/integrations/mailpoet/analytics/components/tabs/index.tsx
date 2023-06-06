import { useCallback, useMemo } from 'react';
import { useHistory, useLocation } from 'react-router-dom';
import { TabPanel } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import { AutomationFlow } from './automation_flow';
import { Emails } from './emails';
import { Orders } from './orders';
import { Subscribers } from './subscribers';
import { automationHasEmails } from '../../helpers/automation';

export function Tabs(): JSX.Element {
  const history = useHistory();
  const location = useLocation();
  const pageParams = useMemo(
    () => new URLSearchParams(location.search),
    [location],
  );
  const currentTab = pageParams.get('tab') ?? 'automation-flow';
  const tabs = [
    {
      name: 'automation-flow',
      title: __('Automation flow', 'mailpoet'),
    },
  ];
  if (automationHasEmails()) {
    tabs.push({
      name: 'automation-emails',
      title: __('Emails', 'mailpoet'),
    });
    tabs.push({
      name: 'automation-orders',
      title: _x('Orders', 'WooCommerce orders', 'mailpoet'),
    });
    tabs.push({
      name: 'automation-subscribers',
      title: __('Subscribers', 'mailpoet'),
    });
  }

  const updateUrlSearchString = useCallback(
    (tab: string) => {
      const newSearch = new URLSearchParams({
        ...Object.fromEntries(pageParams.entries()),
        ...{
          tab,
        },
      });
      history.push({ search: newSearch.toString() });
    },
    [pageParams, history],
  );

  return (
    <div className="mailpoet-analytics-tabs">
      <TabPanel
        onSelect={updateUrlSearchString}
        initialTabName={currentTab}
        tabs={tabs}
      >
        {(tab) => {
          switch (tab.name) {
            case 'automation-flow':
              return <AutomationFlow />;
            case 'automation-emails':
              return <Emails />;
            case 'automation-orders':
              return <Orders />;
            case 'automation-subscribers':
              return <Subscribers />;
            default:
              return null;
          }
        }}
      </TabPanel>
    </div>
  );
}
