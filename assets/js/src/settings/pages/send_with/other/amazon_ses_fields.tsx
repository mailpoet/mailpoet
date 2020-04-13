import React from 'react';
import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import SendingFrequency from './sending_frequency';

export default function AmazonSesFields() {
  const [region, setRegion] = useSetting('mta', 'region');
  const [accessKey, setAccessKey] = useSetting('mta', 'access_key');
  const [secretKey, setSecretKey] = useSetting('mta', 'secret_key');
  return (
    <>
      <SendingFrequency recommendedEmails="100" recommendedInterval="5" />
      <Label title={t('region')} htmlFor="mailpoet_amazon_ses_region" />
      <Inputs>
        <select id="mailpoet_amazon_ses_region" value={region} onChange={onChange(setRegion)}>
          <option value="us-east-1">US East (N. Virginia)</option>
          <option value="us-west-2">US West (Oregon)</option>
          <option value="eu-west-1">EU (Ireland)</option>
          <option value="eu-central-1">EU (Frankfurt)</option>
          <option value="ap-south-1">Asia Pacific (Mumbai)</option>
          <option value="ap-southeast-2">Asia Pacific (Sydney)</option>
        </select>
      </Inputs>
      <Label title={t('accessKey')} htmlFor="mailpoet_amazon_ses_access_key" />
      <Inputs>
        <input
          type="text"
          value={accessKey}
          className="regular-text"
          onChange={onChange(setAccessKey)}
          id="mailpoet_amazon_ses_access_key"
        />
      </Inputs>
      <Label title={t('secretKey')} htmlFor="mailpoet_amazon_ses_secret_key" />
      <Inputs>
        <input
          type="text"
          value={secretKey}
          className="regular-text"
          onChange={onChange(setSecretKey)}
          id="mailpoet_amazon_ses_secret_key"
        />
      </Inputs>
    </>
  );
}
