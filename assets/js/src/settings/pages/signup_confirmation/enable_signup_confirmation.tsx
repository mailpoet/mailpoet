import React from 'react';
import { t, onChange } from 'common/functions';
import { Label, Inputs } from 'settings/components';
import { useSelector, useSetting } from 'settings/store/hooks';

export default function EnableSignupConfirmation() {
  const isMssActive = useSelector('isMssActive')();
  const [enabled, setEnabled] = useSetting('signup_confirmation', 'enabled');
  const handleChange = (value: '1' | '') => {
    // eslint-disable-next-line no-alert
    if (value === '1' && window.confirm(t('subscribersNeedToActivateSub'))) {
      setEnabled('1');
    }
    // eslint-disable-next-line no-alert
    if (value === '' && window.confirm(t('newSubscribersAutoConfirmed'))) {
      setEnabled('');
    }
  };

  return (
    <>
      <Label
        title={t('enableSignupConfTitle')}
        description={(
          <>
            {t('enableSignupConfDescription')}
            {' '}
            <a
              href="https://kb.mailpoet.com/article/128-why-you-should-use-signup-confirmation-double-opt-in"
              data-beacon-article="57ce097f903360649f6e5700"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readAboutDoubleOptIn')}
            </a>
          </>
        )}
        htmlFor="signup_confirmation-enabled"
      />
      <Inputs>
        {isMssActive ? <p>{t('signupConfirmationIsMandatory')}</p> : (
          <>
            <input
              id="signup_confirmation-enabled"
              type="radio"
              checked={enabled === '1'}
              value="1"
              onChange={onChange(handleChange)}
              data-automation-id="enable_signup_confirmation"
            />
            {t('yes')}
            {' '}
            <input
              type="radio"
              checked={enabled === ''}
              value=""
              onChange={onChange(handleChange)}
              data-automation-id="disable_signup_confirmation"
            />
            {t('no')}
          </>
        )}
      </Inputs>
    </>
  );
}
