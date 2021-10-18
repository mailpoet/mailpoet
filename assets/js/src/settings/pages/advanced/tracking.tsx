import React from 'react';

import { t } from 'common/functions';
import Radio from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';
import { GlobalContext } from 'context/index.jsx';

export default function Tracking() {
  const [enabled, setEnabled] = useSetting('tracking', 'enabled');
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const { features } = React.useContext<any>(GlobalContext);

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
          automationId="tracking-enabled-radio"
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
          automationId="tracking-disabled-radio"
        />
        <label htmlFor="tracking-disabled">
          {t('no')}
          {features.isSupported('re-engagement-email') && !enabled && (
            <>
              <br />
              <span className="mailpoet-note">
                {t('re-engagementDisabledBecauseTrackingIs')}
              </span>
            </>
          )}
        </label>
      </Inputs>
    </>
  );
}
