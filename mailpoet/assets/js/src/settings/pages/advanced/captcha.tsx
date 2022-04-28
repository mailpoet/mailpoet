import { useEffect } from 'react';

import { t, onChange } from 'common/functions';
import { Input } from 'common/form/input/input';
import { Radio } from 'common/form/radio/radio';
import { useSetting, useSelector, useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function Captcha() {
  const [type, setType] = useSetting('captcha', 'type');
  const [recaptchaCheckboxToken, setRecaptchaCheckboxToken] = useSetting(
    'captcha',
    'recaptcha_site_token',
  );
  const [recaptchaCheckboxSecret, setRecaptchaCheckboxSecret] = useSetting(
    'captcha',
    'recaptcha_secret_token',
  );
  const [recaptchaInvisibleToken, setRecaptchaInvisibleToken] = useSetting(
    'captcha',
    'recaptcha_invisible_site_token',
  );
  const [recaptchaInvisibleSecret, setRecaptchaInvisibleSecret] = useSetting(
    'captcha',
    'recaptcha_invisible_secret_token',
  );
  const hasBuiltInCaptcha = useSelector('isBuiltInCaptchaSupported')();
  const setErrorFlag = useAction('setErrorFlag');
  const missingRecaptchaCheckboxToken =
    type === 'recaptcha' && recaptchaCheckboxToken.trim() === '';
  const missingRecaptchaCheckboxSecret =
    type === 'recaptcha' && recaptchaCheckboxSecret.trim() === '';
  const missingRecaptchaInvisibleToken =
    type === 'recaptcha-invisible' && recaptchaInvisibleToken.trim() === '';
  const missingRecaptchaInvisibleSecret =
    type === 'recaptcha-invisible' && recaptchaInvisibleSecret.trim() === '';
  useEffect(() => {
    setErrorFlag(
      missingRecaptchaCheckboxToken ||
        missingRecaptchaCheckboxSecret ||
        missingRecaptchaInvisibleSecret ||
        missingRecaptchaInvisibleToken,
    );
  }, [
    missingRecaptchaCheckboxSecret,
    missingRecaptchaCheckboxToken,
    missingRecaptchaInvisibleSecret,
    missingRecaptchaInvisibleToken,
    setErrorFlag,
  ]);

  return (
    <>
      <Label
        title={t('captchaTitle')}
        description={
          <>
            {t('captchaDescription')}{' '}
            <a
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/182-add-a-captcha-to-subscription-forms"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readMore')}
            </a>
            {(type === 'recaptcha' || type === 'recaptcha-invisible') && (
              <p>
                <span>{t('reCaptchaDescription')} </span>
                <a
                  className="mailpoet-link"
                  href="https://www.google.com/recaptcha/admin"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {t('signupForCaptchaKey')}
                </a>
              </p>
            )}
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
          <label htmlFor="google-captcha">{t('googleReCaptchaCheckbox')}</label>
        </div>
        {type === 'recaptcha' && (
          <div className="mailpoet-settings-inputs-row">
            <Input
              dimension="small"
              type="text"
              value={recaptchaCheckboxToken}
              onChange={onChange(setRecaptchaCheckboxToken)}
              placeholder={t('yourReCaptchaKey')}
            />
            {missingRecaptchaCheckboxToken && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
            <br />
            <Input
              dimension="small"
              type="text"
              value={recaptchaCheckboxSecret}
              onChange={onChange(setRecaptchaCheckboxSecret)}
              placeholder={t('yourReCaptchaSecret')}
            />
            {missingRecaptchaCheckboxSecret && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
          </div>
        )}
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="google-captcha-invisible"
            value="recaptcha-invisible"
            checked={type === 'recaptcha-invisible'}
            onCheck={setType}
          />
          <label htmlFor="google-captcha-invisible">
            {t('googleReCaptchaInvisible')}
          </label>
        </div>
        {type === 'recaptcha-invisible' && (
          <div className="mailpoet-settings-inputs-row">
            <Input
              dimension="small"
              type="text"
              value={recaptchaInvisibleToken}
              onChange={onChange(setRecaptchaInvisibleToken)}
              placeholder={t('yourReCaptchaKey')}
            />
            {missingRecaptchaInvisibleToken && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('fillReCaptchaKeys')}
              </span>
            )}
            <br />
            <Input
              dimension="small"
              type="text"
              value={recaptchaInvisibleSecret}
              onChange={onChange(setRecaptchaInvisibleSecret)}
              placeholder={t('yourReCaptchaSecret')}
            />
            {missingRecaptchaInvisibleSecret && (
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
