import { useEffect } from 'react';
import { isEmail, t, onChange, setLowercaseValue } from 'common/functions';
import Checkbox from 'common/form/checkbox/checkbox';
import Input from 'common/form/input/input';
import { useSetting, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function StatsNotifications() {
  const [enabled, setEnabled] = useSetting('stats_notifications', 'enabled');
  const [automated, setAutomated] = useSetting(
    'stats_notifications',
    'automated',
  );
  const [email, setEmail] = useSetting('stats_notifications', 'address');
  const setErrorFlag = useAction('setErrorFlag');
  const hasError =
    (enabled === '1' || automated === '1') && email.trim() === '';
  const invalidEmail = email && !isEmail(email);
  useEffect(() => {
    setErrorFlag(hasError || invalidEmail);
  }, [hasError, invalidEmail, setErrorFlag]);

  return (
    <>
      <Label
        title={t('statsNotifsTitle')}
        description={t('statsNotifsDescription')}
        htmlFor="stats-enabled"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Checkbox
            id="stats-enabled"
            checked={enabled === '1'}
            onCheck={(isChecked) => setEnabled(isChecked ? '1' : '0')}
          />
          <label htmlFor="stats-enabled">{t('newslettersAndPostNotifs')}</label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Checkbox
            id="stats-automated"
            checked={automated === '1'}
            onCheck={(isChecked) => setAutomated(isChecked ? '1' : '0')}
          />
          <label htmlFor="stats-automated">{t('welcomeAndWcEmails')}</label>
        </div>
        <Input
          dimension="small"
          type="email"
          value={email}
          onChange={onChange(setLowercaseValue(setEmail))}
          placeholder="me@mydomain.com"
        />
        {hasError && (
          <div className="mailpoet_error_item mailpoet_error">
            {t('pleaseFillEmail')}
          </div>
        )}
        {invalidEmail && (
          <div className="mailpoet_error_item mailpoet_error">
            {t('invalidEmail')}
          </div>
        )}
      </Inputs>
    </>
  );
}
