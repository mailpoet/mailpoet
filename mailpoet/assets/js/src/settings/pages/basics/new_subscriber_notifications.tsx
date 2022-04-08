import { useEffect } from 'react';
import { isEmail, t, onChange, setLowercaseValue } from 'common/functions';
import Input from 'common/form/input/input';
import Radio from 'common/form/radio/radio';
import { useSetting, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function NewSubscriberNotifications() {
  const [enabled, setEnabled] = useSetting(
    'subscriber_email_notification',
    'enabled',
  );
  const [email, setEmail] = useSetting(
    'subscriber_email_notification',
    'address',
  );
  const setErrorFlag = useAction('setErrorFlag');
  const hasError = enabled === '1' && email.trim() === '';
  const invalidEmail = email && !isEmail(email);
  useEffect(() => {
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
        <Radio checked={enabled === '1'} value="1" onCheck={setEnabled} />
        {t('yes')}{' '}
        <Radio checked={enabled === ''} value="" onCheck={setEnabled} />
        {t('no')}
        <br />
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
