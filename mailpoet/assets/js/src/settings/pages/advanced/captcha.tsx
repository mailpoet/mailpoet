import { useEffect } from 'react';

import { t, onChange } from 'common/functions';
import Input from 'common/form/input/input';
import Radio from 'common/form/radio/radio';
import { useSetting, useSelector, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Captcha() {
  const [type, setType] = useSetting('captcha', 'type');
  const [token, setToken] = useSetting('captcha', 'recaptcha_site_token');
  const [secret, setSecret] = useSetting('captcha', 'recaptcha_secret_token');
  const hasBuiltInCaptcha = useSelector('isBuiltInCaptchaSupported')();
  const setErrorFlag = useAction('setErrorFlag');
  const missingToken = type === 'recaptcha' && token.trim() === '';
  const missingSecret = type === 'recaptcha' && secret.trim() === '';
  useEffect(() => {
    setErrorFlag(missingToken || missingSecret);
  }, [missingSecret, missingToken, setErrorFlag]);

  return (
    <>
      <Label
        title={t('captchaTitle')}
        description={
          <>
            {t('captchaDescription')}{' '}
            <a
              className="mailpoet-link"
              href="https://www.google.com/recaptcha/admin"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('signupForCaptchaKey')}
            </a>
          </>
        }
        htmlFor=""
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="built-in-captcha"
            disabled={!hasBuiltInCaptcha}
            value="built-in"
            checked={type === 'built-in'}
            onCheck={setType}
          />
          <label htmlFor="built-in-captcha">
            {t('builtInCaptcha')}{' '}
            {!hasBuiltInCaptcha && t('disbaledBecauseExtensionMissing')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="google-captcha"
            value="recaptcha"
            checked={type === 'recaptcha'}
            onCheck={setType}
          />
          <label htmlFor="google-captcha">{t('googleReCaptcha')}</label>
        </div>
        {type === 'recaptcha' && (
          <div className="mailpoet-settings-inputs-row">
            <Input
              dimension="small"
              type="text"
              value={token}
              onChange={onChange(setToken)}
              placeholder={t('yourReCaptchaKey')}
            />
            {missingToken && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
            <br />
            <Input
              dimension="small"
              type="text"
              value={secret}
              onChange={onChange(setSecret)}
              placeholder={t('yourReCaptchaSecret')}
            />
            {missingSecret && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
          </div>
        )}
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="no-captcha"
            value=""
            checked={type === ''}
            onCheck={setType}
          />
          <label htmlFor="no-captcha">{t('disable')}</label>
        </div>
      </Inputs>
    </>
  );
}
