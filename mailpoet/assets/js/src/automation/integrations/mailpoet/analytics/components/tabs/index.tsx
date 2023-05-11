import { __, _x } from '@wordpress/i18n';
import { RoutedTabs } from '../../../../../../common/tabs/routed_tabs';
import { Tab } from '../../../../../../common';
import { AutomationFlow } from './automation_flow';
import { Emails } from './emails';
import { Orders } from './orders';
import { Subscribers } from './subscribers';
import { automationHasEmails } from '../../helpers/automation';

export function Tabs(): JSX.Element {
  const tabs = [
    <Tab
      key="automation-flow"
      route="automation-flow/(.*)?"
      title={__('Automation flow', 'mailpoet')}
      automationId="tab-automation-flow"
    >
      <AutomationFlow />
    </Tab>,
  ];

  if (automationHasEmails()) {
    tabs.push(
      <Tab
        key="automation-emails"
        route="automation-emails/(.*)?"
        title={__('Emails', 'mailpoet')}
        automationId="tab-automation-emails"
      >
        <Emails />
      </Tab>,
    );
    tabs.push(
      <Tab
        key="automation-orders"
        route="automation-orders/(.*)?"
        title={_x('Orders', 'WooCommerce orders', 'mailpoet')}
        automationId="tab-automation-orders"
      >
        <Orders />
      </Tab>,
    );
    tabs.push(
      <Tab
        key="automation-subscribers"
        route="automation-subscribers/(.*)?"
        title={__('Subscribers', 'mailpoet')}
        automationId="tab-automation-subscribers"
      >
        <Subscribers />
      </Tab>,
    );
  }
  return (
    <div className="mailpoet-analytics-tabs">
      <RoutedTabs activeKey="automation-flow" routerType="switch-only">
        {tabs}
      </RoutedTabs>
    </div>
  );
}
