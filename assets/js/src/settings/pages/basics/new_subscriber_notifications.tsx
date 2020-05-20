import React from 'react';
import {
  isEmail,
  t,
  onChange,
  setLowercaseValue,
} from 'common/functions';
import { useSetting, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function NewSubscriberNotifications() {
  const [enabled, setEnabled] = useSetting('subscriber_email_notification', 'enabled');
  const [email, setEmail] = useSetting('subscriber_email_notification', 'address');
  const setErrorFlag = useAction('setErrorFlag');
  const hasError = enabled === '1' && email.trim() === '';
  const invalidEmail = email && !isEmail(email);
  React.useEffect(() => {
    setErrorFlag(hasError || invalidEmail);
  }, [hasError, invalidEmail, setErrorFlag]);

  return (
    <>
      <Label
        title={t('newSubscriberNotifsTitle')}
        description={t('newSubscriberNotifsDescription')}
        htmlFor="subscriber_email_notification-enabled"
      />
      <Inputs>
        <input
          type="radio"
          checked={enabled === '1'}
          value="1"
          onChange={onChange(setEnabled)}
        />
        {t('yes')}
        {' '}
        <input
          type="radio"
          checked={enabled === ''}
          value=""
          onChange={onChange(setEnabled)}
        />
        {t('no')}
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
