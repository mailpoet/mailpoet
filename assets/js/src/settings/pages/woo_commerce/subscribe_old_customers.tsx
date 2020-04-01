import React from 'react';
import { t, onToggle } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function SubscribeOldCustomers() {
  const [enabled, setEnabled] = useSetting('mailpoet_subscribe_old_woocommerce_customers', 'enabled');

  return (
    <>
      <Label
        title={t('subscribeOldWCTitle')}
        description={t('subscribeOldWCDescription')}
        htmlFor="mailpoet_subscribe_old_wc_customers"
      />
      <Inputs>
        <input
          type="checkbox"
          id="mailpoet_subscribe_old_wc_customers"
          data-automation-id="mailpoet_subscribe_old_wc_customers"
          checked={enabled === '1'}
          onChange={onToggle(setEnabled, '')}
        />
      </Inputs>
    </>
  );
}
