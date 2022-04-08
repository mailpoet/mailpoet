import { useEffect } from 'react';
import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import Select from 'common/form/select/select';
import { useSetting } from 'settings/store/hooks';

export default function SendingMethod() {
  const [provider, setProvider] = useSetting('smtp_provider');
  const [, setGroup] = useSetting('mta_group');
  const [, setMethod] = useSetting('mta', 'method');
  useEffect(() => {
    switch (provider) {
      case 'server':
        setGroup('website');
        setMethod('PHPMail');
        break;
      case 'manual':
        setGroup('smtp');
        setMethod('SMTP');
        break;
      case 'AmazonSES':
        setGroup('smtp');
        setMethod('AmazonSES');
        break;
      case 'SendGrid':
        setGroup('smtp');
        setMethod('SendGrid');
        break;
      default:
        setMethod('PHPMail');
    }
  }, [provider, setGroup, setMethod]);

  return (
    <>
      <Label title={t('method')} htmlFor="mailpoet_smtp_method" />
      <Inputs>
        <Select
          id="mailpoet_smtp_method"
          value={provider}
          onChange={onChange(setProvider)}
          isMinWidth
          dimension="small"
        >
          <option value="server">{t('hostOption')}</option>
          <option value="manual">{t('smtpOption')}</option>
          <optgroup label={t('selectProvider')}>
            <option value="AmazonSES">Amazon SES</option>
            <option value="SendGrid">SendGrid</option>
          </optgroup>
        </Select>
      </Inputs>
    </>
  );
}
