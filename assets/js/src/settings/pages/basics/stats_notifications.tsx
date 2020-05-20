import React from 'react';
import {
  isEmail,
  t,
  onChange,
  onToggle,
  setLowercaseValue,
} from 'common/functions';
import { useSetting, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function StatsNotifications() {
  const [enabled, setEnabled] = useSetting('stats_notifications', 'enabled');
  const [automated, setAutomated] = useSetting('stats_notifications', 'automated');
  const [email, setEmail] = useSetting('stats_notifications', 'address');
  const setErrorFlag = useAction('setErrorFlag');
  const hasError = (enabled === '1' || automated === '1') && email.trim() === '';
  const invalidEmail = email && !isEmail(email);
  React.useEffect(() => {
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
        <input
          type="checkbox"
          id="stats-enabled"
          checked={enabled === '1'}
          onChange={onToggle(setEnabled)}
        />
        <label htmlFor="stats-enabled">{t('newslettersAndPostNotifs')}</label>
        <br />
        <input
          type="checkbox"
          id="stats-automated"
          checked={automated === '1'}
          onChange={onToggle(setAutomated)}
        />
        <label htmlFor="stats-automated">{t('welcomeAndWcEmails')}</label>
        <br />
        <input type="email" value={email} onChange={onChange(setLowercaseValue(setEmail))} placeholder="me@mydomain.com" />
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
