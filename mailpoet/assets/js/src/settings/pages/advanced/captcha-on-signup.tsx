import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSelector, useSetting } from 'settings/store/hooks';
import { Inputs, Label } from 'settings/components';

export function CaptchaOnSignup() {
  const [enabled, setEnabled] = useSetting(
    'captcha',
    'on_register_forms',
    'enabled',
  );
  const hasWooCommerce = useSelector('hasWooCommerce')();

  return (
    <>
      <Label
        title={t('captchaOnRegisterTitle')}
        description={t(
          hasWooCommerce
            ? 'captchaOnRegisterWooActiveDescription'
            : 'captchaOnRegisterWooInactiveDescription',
        )}
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="captcha-on-register-enabled"
          value="1"
          checked={enabled === '1'}
          onCheck={setEnabled}
        />
        <label htmlFor="captcha-on-register-enabled">{t('yes')}</label>
        <span className="mailpoet-gap" />
        <Radio
          id="captcha-on-register-disabled"
          value=""
          checked={enabled === ''}
          onCheck={setEnabled}
        />
        <label htmlFor="captcha-on-register-disabled">{t('no')}</label>
      </Inputs>
    </>
  );
}
