import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import Select from 'common/form/select/select';
import { useSetting, useSelector } from 'settings/store/hooks';
import SendingFrequency from './sending_frequency';

export default function PHPMailFields() {
  const [hostName, setHostName] = useSetting('web_host');
  const hosts = useSelector('getWebHosts')();
  const host = hosts[hostName];
  return (
    <>
      <Label title={t('yourHost')} htmlFor="mailpoet_web_host" />
      <Inputs>
        <Select
          id="mailpoet_web_host"
          value={hostName}
          onChange={onChange(setHostName)}
          isMinWidth
          dimension="small"
        >
          {Object.entries(hosts).map(([key, h]) => (
            <option key={key} value={key}>
              {h.name}
            </option>
          ))}
        </Select>
      </Inputs>
      <SendingFrequency
        recommendedEmails={host.emails}
        recommendedInterval={host.interval}
      />
    </>
  );
}
