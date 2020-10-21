import React from 'react';

import { t } from 'common/functions';
import Radio from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function InactiveSubscribers() {
  const [duration, setDuration] = useSetting('deactivate_subscriber_after_inactive_days');
  const [trackingEnabled] = useSetting('tracking', 'enabled');
  return (
    <>
      <Label
        title={t('inactiveSubsTitle')}
        description={(
          <>
            {t('inactiveSubsDescription')}
            {' '}
            <a
              href="https://kb.mailpoet.com/article/264-inactive-subscribers"
              data-beacon-article="5cbf19622c7d3a026fd3efe1"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readMore')}
            </a>
          </>
        )}
        htmlFor=""
      />
      <Inputs>
        {!trackingEnabled && <p data-automation-id="inactive-subscribers-disabled">{t('disabledBecauseTrackingIs')}</p>}
        {trackingEnabled && (
          <div data-automation-id="inactive-subscribers-enabled">
            <Radio
              id="inactive-subscribers-disabled"
              data-automation-id="inactive-subscribers-option-never"
              value=""
              checked={duration === ''}
              onCheck={setDuration}
            />
            <label htmlFor="inactive-subscribers-disabled">
              {t('never')}
            </label>
            <br />
            <Radio
              id="inactive-subscribers-3-months"
              value="90"
              checked={duration === '90'}
              onCheck={setDuration}
            />
            <label htmlFor="inactive-subscribers-3-months">
              {t('after3months')}
            </label>
            <br />
            <Radio
              id="inactive-subscribers-6-months"
              value="180"
              checked={duration === '180'}
              onCheck={setDuration}
              data-automation-id="inactive-subscribers-default"
            />
            <label htmlFor="inactive-subscribers-6-months">
              {t('after6months')}
            </label>
            <br />
            <Radio
              id="inactive-subscribers-12-months"
              value="365"
              checked={duration === '365'}
              onCheck={setDuration}
            />
            <label htmlFor="inactive-subscribers-12-months">
              {t('after12months')}
            </label>
          </div>
        )}
      </Inputs>
    </>
  );
}
