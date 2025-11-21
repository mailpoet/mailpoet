import { useMemo } from 'react';
import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import { Select } from 'common/form/select/select';
import { useSetting, useSelector } from 'settings/store/hooks';
import { SendingFrequency } from './sending-frequency';

type WebHost = {
  name: string;
  emails: number;
  interval: number;
};

export function PHPMailFields() {
  const [hostName, setHostName] = useSetting('web_host');
  const hosts = useSelector('getWebHosts');
  const host = useMemo(
    () => hosts[hostName] as WebHost | undefined,
    [hosts, hostName],
  );
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
              {(h as WebHost).name}
            </option>
          ))}
        </Select>
      </Inputs>
      <SendingFrequency
        recommendedEmails={host?.emails ?? 25}
        recommendedInterval={host?.interval ?? 5}
      />
    </>
  );
}
