import React from 'react';

import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function ShareData() {
  const [enabled, setEnabled] = useSetting('analytics', 'enabled');
  const [, set3rdPartyLibsEnabled] = useSetting('3rd_party_libs', 'enabled');

  return (
    <>
      <Label
        title={t('shareDataTitle')}
        description={(
          <>
            {t('shareDataDescription')}
            {' '}
            <a
              href="https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
              data-beacon-article="57ce0aaac6979108399a0454"
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
          id="share-data-enabled"
          value="1"
          checked={enabled === '1'}
          onChange={onChange(() => {
            setEnabled('1');
            set3rdPartyLibsEnabled('1');
          })}
          data-automation-id="analytics-yes"
        />
        <label htmlFor="share-data-enabled">
          {t('yes')}
        </label>
        {' '}
        <input
          type="radio"
          id="share-data-disabled"
          value=""
          checked={enabled === ''}
          onChange={onChange(setEnabled)}
          data-automation-id="analytics-no"
        />
        <label htmlFor="share-data-disabled">
          {t('no')}
        </label>
      </Inputs>
    </>
  );
}
