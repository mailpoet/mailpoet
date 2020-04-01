import React from 'react';
import { SaveButton } from 'settings/components';
import EmailCustomizer from './email_customizer';
import CheckoutOptin from './checkout_optin';

export default function WooCommerce() {
  return (
    <div className="mailpoet-settings-grid">
      <EmailCustomizer />
      <CheckoutOptin />
      <SaveButton />
    </div>
  );
}
