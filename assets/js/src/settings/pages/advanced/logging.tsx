import React from 'react';

import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Logging() {
  const [level, setLevel] = useSetting('logging');
  return (
    <>
      <Label
        title={t('loggingTitle')}
        description={t('loggingDescription')}
        htmlFor="logging-level"
      />
      <Inputs>
        <select id="logging-level" value={level} onChange={onChange(setLevel)} data-automation-id="logging-select-box">
          <option value="everything" data-automation-id="log-everything">{t('everythingLogOption')}</option>
          <option value="errors" data-automation-id="log-errors">{t('errorsLogOption')}</option>
          <option value="nothing" data-automation-id="log-nothing">{t('nothingLogOption')}</option>
        </select>
      </Inputs>
    </>
  );
}
