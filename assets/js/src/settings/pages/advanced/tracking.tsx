import React from 'react';

import { t, onChange } from 'common/functions';
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
        <input
          type="radio"
          id="tracking-enabled"
          value="1"
          checked={enabled === '1'}
          onChange={onChange(setEnabled)}
          data-automation-id="tracking-enabled-radio"
        />
        <label htmlFor="tracking-enabled">
          {t('yes')}
        </label>
        {' '}
        <input
          type="radio"
          id="tracking-disabled"
          value=""
          checked={enabled === ''}
          onChange={onChange(setEnabled)}
          data-automation-id="tracking-disabled-radio"
        />
        <label htmlFor="tracking-disabled">
          {t('no')}
        </label>
      </Inputs>
    </>
  );
}
