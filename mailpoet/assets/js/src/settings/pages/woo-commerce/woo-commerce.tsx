import { SaveButton } from 'settings/components';
import { AutomationsInfoNotice } from 'notices/automations_info_notice';
import { EmailCustomizer } from './email_customizer';
import { CheckoutOptin } from './checkout_optin';
import { SubscribeOldCustomers } from './subscribe_old_customers';

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
