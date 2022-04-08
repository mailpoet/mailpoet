import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import Input from 'common/form/input/input';
import Radio from 'common/form/radio/radio';
import Select from 'common/form/select/select';
import { useSetting } from 'settings/store/hooks';
import SendingFrequency from './sending_frequency';

export default function SmtpFields() {
  const [host, setHost] = useSetting('mta', 'host');
  const [port, setPort] = useSetting('mta', 'port');
  const [login, setLogin] = useSetting('mta', 'login');
  const [password, setPassword] = useSetting('mta', 'password');
  const [encryption, setEncryption] = useSetting('mta', 'encryption');
  const [authentication, setAuthentication] = useSetting(
    'mta',
    'authentication',
  );
  return (
    <>
      <SendingFrequency recommendedEmails={100} recommendedInterval={5} />
      <Label
        title={t('smtpHost')}
        description={t('smtpHostExample')}
        htmlFor="mailpoet_smtp_host"
      />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          id="mailpoet_smtp_host"
          value={host}
          onChange={onChange(setHost)}
        />
      </Inputs>
      <Label title={t('smtpPort')} htmlFor="mailpoet_smtp_port" />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          id="mailpoet_smtp_port"
          value={port}
          onChange={onChange(setPort)}
        />
      </Inputs>
      <Label title={t('login')} htmlFor="mailpoet_smtp_login" />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          id="mailpoet_smtp_login"
          value={login}
          onChange={onChange(setLogin)}
        />
      </Inputs>
      <Label title={t('password')} htmlFor="mailpoet_smtp_password" />
      <Inputs>
        <Input
          dimension="small"
          type="password"
          id="mailpoet_smtp_password"
          value={password}
          onChange={onChange(setPassword)}
        />
      </Inputs>
      <Label
        title={t('secureConnectioon')}
        htmlFor="mailpoet_smtp_encryption"
      />
      <Inputs>
        <Select
          id="mailpoet_smtp_encryption"
          value={encryption}
          onChange={onChange(setEncryption)}
          isMinWidth
          dimension="small"
        >
          <option value="">{t('no')}</option>
          <option value="ssl">SSL</option>
          <option value="tls">TLS</option>
        </Select>
      </Inputs>
      <Label
        title={t('authentication')}
        description={t('authenticationDescription')}
        htmlFor="mailpoet_smtp_authentication"
      />
      <Inputs>
        <Radio
          value="1"
          checked={authentication === '1'}
          onCheck={setAuthentication}
        />
        {t('yes')}{' '}
        <Radio
          value="-1"
          checked={authentication === '-1'}
          onCheck={setAuthentication}
        />
        {t('no')}
      </Inputs>
    </>
  );
}
