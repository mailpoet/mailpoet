import React from 'react';
import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import { useSetting, useSelector } from 'settings/store/hooks';
import SendingFrequency from './sending_frequency';

export default function SendGridFields() {
  const [apiKey, setApiKey] = useSetting('mta', 'api_key');
  const options = useSelector('getSendGridOptions')();
  return (
    <>
      <SendingFrequency recommendedEmails={options.emails} recommendedInterval={options.interval} />
      <Label title={t('apiKey')} htmlFor="mailpoet_sendgrid_api_key" />
      <Inputs>
        <input
          type="text"
          value={apiKey}
          className="regular-text"
          onChange={onChange(setApiKey)}
          id="mailpoet_sendgrid_api_key"
        />
      </Inputs>
    </>
  );
}
