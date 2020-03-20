import React from 'react';

import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Transactional() {
  const [enabled, setEnabled] = useSetting('send_transactional_emails');

  return (
    <>
      <Label
        title={t('transactionalTitle')}
        description={(
          <>
            {t('transactionalDescription')}
            {' '}
            <a
              href="https://kb.mailpoet.com/article/292-choose-how-to-send-your-wordpress-websites-emails"
              data-beacon-article="5ddbf92504286364bc9228c5"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('transactionalLink')}
            </a>
          </>
        )}
        htmlFor=""
      />
      <Inputs>
        <input
          type="radio"
          id="transactional-enabled"
          value="1"
          checked={enabled === '1'}
          onChange={onChange(setEnabled)}
        />
        <label htmlFor="transactional-enabled">
          {t('transactionalCurrentMethod')}
        </label>
        <br />
        <input
          type="radio"
          id="transactional-disabled"
          value=""
          checked={enabled === ''}
          onChange={onChange(setEnabled)}
        />
        <label htmlFor="transactional-disabled">
          {t('transactionalWP')}
        </label>
      </Inputs>
    </>
  );
}
