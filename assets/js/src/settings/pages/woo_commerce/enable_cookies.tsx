import React from 'react';
import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';
import Checkbox from 'common/form/checkbox/checkbox';

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
        <Checkbox
          id="mailpoet_accept_cookie_revenue_tracking"
          automationId="accept_cookie_revenue_tracking"
          checked={enabled === '1'}
          onCheck={(isChecked) => setEnabled(isChecked ? '1' : '')}
        />
      </Inputs>
    </>
  );
}
