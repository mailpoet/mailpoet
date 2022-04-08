import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import Input from 'common/form/input/input';
import Select from 'common/form/select/select';
import { useSetting, useSelector } from 'settings/store/hooks';
import SendingFrequency from './sending_frequency';

export default function AmazonSesFields() {
  const [region, setRegion] = useSetting('mta', 'region');
  const [accessKey, setAccessKey] = useSetting('mta', 'access_key');
  const [secretKey, setSecretKey] = useSetting('mta', 'secret_key');
  const options = useSelector('getAmazonSesOptions')();

  return (
    <>
      <SendingFrequency
        recommendedEmails={options.emails}
        recommendedInterval={options.interval}
      />
      <Label title={t('region')} htmlFor="mailpoet_amazon_ses_region" />
      <Inputs>
        <Select
          id="mailpoet_amazon_ses_region"
          value={region}
          onChange={onChange(setRegion)}
          isMinWidth
          dimension="small"
        >
          {Object.entries(options.regions).map(([label, name]) => (
            <option key={name} value={name}>
              {label}
            </option>
          ))}
        </Select>
      </Inputs>
      <Label title={t('accessKey')} htmlFor="mailpoet_amazon_ses_access_key" />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          value={accessKey}
          className="regular-text"
          onChange={onChange(setAccessKey)}
          id="mailpoet_amazon_ses_access_key"
        />
      </Inputs>
      <Label title={t('secretKey')} htmlFor="mailpoet_amazon_ses_secret_key" />
      <Inputs>
        <Input
          dimension="small"
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
