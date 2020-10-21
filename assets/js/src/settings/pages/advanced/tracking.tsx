import React from 'react';

import { t } from 'common/functions';
import Radio from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Tracking() {
  const [enabled, setEnabled] = useSetting('tracking', 'enabled');

  return (
    <>
      <Label
        title={t('trackingTitle')}
        description={t('trackingDescription')}
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="tracking-enabled"
          value="1"
          checked={enabled === '1'}
          onCheck={setEnabled}
          data-automation-id="tracking-enabled-radio"
        />
        <label htmlFor="tracking-enabled">
          {t('yes')}
        </label>
        <span className="mailpoet-gap" />
        <Radio
          id="tracking-disabled"
          value=""
          checked={enabled === ''}
          onCheck={setEnabled}
          data-automation-id="tracking-disabled-radio"
        />
        <label htmlFor="tracking-disabled">
          {t('no')}
        </label>
      </Inputs>
    </>
  );
}
