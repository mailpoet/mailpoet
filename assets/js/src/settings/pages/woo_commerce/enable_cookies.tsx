import React from 'react';
import { t, onToggle } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function EnableCookies() {
  const [enabled, setEnabled] = useSetting('woocommerce', 'accept_cookie_revenue_tracking', 'enabled');

  return (
    <>
      <Label
        title={t('enableCookiesTitle')}
        description={t('enableCookiesDescription')}
        htmlFor="mailpoet_accept_cookie_revenue_tracking"
      />
      <Inputs>
        <input
          type="checkbox"
          id="mailpoet_accept_cookie_revenue_tracking"
          data-automation-id="accept_cookie_revenue_tracking"
          checked={enabled === '1'}
          onChange={onToggle(setEnabled, '')}
        />
      </Inputs>
    </>
  );
}
