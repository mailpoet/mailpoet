import React from 'react';

import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export const Libs3rdParty = () => {
  const [enabled, setEnabled] = useSetting('3rd_party_libs', 'enabled');
  const [, setAnalyticsEnabled] = useSetting('analytics', 'enabled');

  return (
    <>
      <Label
        title={t('libs3rdPartyTitle')}
        description={(
          <>
            {t('libs3rdPartyDescription')}
            {' '}
            <a
              href="https://kb.mailpoet.com/article/338-what-3rd-party-libraries-we-use"
              data-beacon-article="5f7c7dd94cedfd0017dcece8"
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
        <input
          type="radio"
          id="libs-3rd-party-enabled"
          value="1"
          checked={enabled === '1'}
          onChange={onChange(setEnabled)}
        />
        <label htmlFor="libs-3rd-party-enabled">
          {t('yes')}
        </label>
        {' '}
        <input
          type="radio"
          id="libs-3rd-party-disabled"
          value=""
          checked={enabled === ''}
          onChange={onChange((value) => {
            setEnabled('');
            setAnalyticsEnabled('');
          })}
        />
        <label htmlFor="libs-3rd-party-disabled">
          {t('no')}
        </label>
      </Inputs>
    </>
  );
};
