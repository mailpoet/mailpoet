import React from 'react';

import { t, onChange } from 'common/functions';
import { useSetting, useSelector, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Captcha() {
  const [type, setType] = useSetting('captcha', 'type');
  const [token, setToken] = useSetting('captcha', 'recaptcha_site_token');
  const [secret, setSecret] = useSetting('captcha', 'recaptcha_secret_token');
  const hasBuiltInCaptcha = useSelector('isBuiltInCaptchaSupported')();
  const setErrorFlag = useAction('setErrorFlag');
  const missingToken = (type === 'recaptcha' && token.trim() === '');
  const missingSecret = (type === 'recaptcha' && secret.trim() === '');
  React.useEffect(() => {
    setErrorFlag(missingToken || missingSecret);
  }, [missingSecret, missingToken, setErrorFlag]);

  return (
    <>
      <Label
        title={t('captchaTitle')}
        description={(
          <>
            {t('captchaDescription')}
            {' '}
            <a
              href="https://www.google.com/recaptcha/admin"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('signupForCaptchaKey')}
            </a>
          </>
        )}
        htmlFor=""
      />
      <Inputs>
        <input
          type="radio"
          id="built-in-captcha"
          disabled={!hasBuiltInCaptcha}
          value="built-in"
          checked={type === 'built-in'}
          onChange={onChange(setType)}
        />
        <label htmlFor="built-in-captcha">
          {t('builtInCaptcha')}
          {' '}
          {!hasBuiltInCaptcha && t('disbaledBecauseExtensionMissing')}
        </label>
        <br />
        <input
          type="radio"
          id="google-captcha"
          value="recaptcha"
          checked={type === 'recaptcha'}
          onChange={onChange(setType)}
        />
        <label htmlFor="google-captcha">
          {t('googleReCaptcha')}
        </label>
        {type === 'recaptcha' && (
          <>
            <br />
            <input
              type="text"
              value={token}
              className="regular-text"
              onChange={onChange(setToken)}
              placeholder={t('yourReCaptchaKey')}
            />
            {missingToken && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
            <br />
            <input
              type="text"
              value={secret}
              className="regular-text"
              onChange={onChange(setSecret)}
              placeholder={t('yourReCaptchaSecret')}
            />
            {missingSecret && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
          </>
        )}
        <br />
        <input
          type="radio"
          id="no-captcha"
          value=""
          checked={type === ''}
          onChange={onChange(setType)}
        />
        <label htmlFor="no-captcha">
          {t('disable')}
        </label>
      </Inputs>
    </>
  );
}
