import React from 'react';
import { SaveButton } from 'settings/components';
import EmailCustomizer from './email_customizer';

export default function WooCommerce() {
  return (
    <div className="mailpoet-settings-grid">
      <EmailCustomizer />
      <SaveButton />
    </div>
  );
}
