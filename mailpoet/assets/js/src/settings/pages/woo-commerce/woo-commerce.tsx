import { SaveButton } from 'settings/components';
import { EmailCustomizer } from './email-customizer';
import { CheckoutOptin } from './checkout-optin';
import { SubscribeOldCustomers } from './subscribe-old-customers';

export function WooCommerce() {
  return (
    <div className="mailpoet-settings-grid">
      <EmailCustomizer />
      <CheckoutOptin />
      <SubscribeOldCustomers />
      <SaveButton />
    </div>
  );
}
