import { SaveButton } from 'settings/components';
import { AutomationsInfoNotice } from 'notices/automations-info-notice';
import { EmailCustomizer } from './email-customizer';
import { CheckoutOptin } from './checkout-optin';
import { SubscribeOldCustomers } from './subscribe-old-customers';

export function WooCommerce() {
  return (
    <>
      <AutomationsInfoNotice />
      <div className="mailpoet-settings-grid">
        <EmailCustomizer />
        <CheckoutOptin />
        <SubscribeOldCustomers />
        <SaveButton />
      </div>
    </>
  );
}
