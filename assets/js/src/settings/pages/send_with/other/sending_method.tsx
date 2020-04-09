import React from 'react';
import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import { useSetting } from 'settings/store/hooks';

export default function SendingMethod() {
  const [provider, setProvider] = useSetting('smtp_provider');
  const [, setMethod] = useSetting('mta', 'method');
  const updateProvider = (value: 'server' | 'manual' | 'AmazonSES' | 'SendGrid') => {
    setProvider(value);
    switch (value) {
      case 'server': setMethod('PHPMail'); break;
      case 'manual': setMethod('SMTP'); break;
      case 'AmazonSES': setMethod('AmazonSES'); break;
      case 'SendGrid': setMethod('AmazonSES'); break;
      default:
    }
  };

  return (
    <>
      <Label title={t('method')} htmlFor="mailpoet_smtp_method" />
      <Inputs>
        <select id="mailpoet_smtp_method" value={provider} onChange={onChange(updateProvider)}>
          <option value="server">{t('hostOption')}</option>
          <option value="manual">{t('smtpOption')}</option>
          <optgroup label={t('selectProvider')}>
            <option value="AmazonSES">Amazon SES</option>
            <option value="SendGrid">SendGrid</option>
          </optgroup>
        </select>
      </Inputs>
    </>
  );
}
