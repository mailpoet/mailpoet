import { t } from 'common/functions';
import Radio from 'common/form/radio/radio';
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
        description={
          <>
            {t('enableSignupConfDescription')}{' '}
            <a
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/128-why-you-should-use-signup-confirmation-double-opt-in"
              data-beacon-article="57ce097f903360649f6e5700"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readAboutDoubleOptIn')}
            </a>
          </>
        }
        htmlFor="signup_confirmation-enabled"
      />
      <Inputs>
        {isMssActive ? (
          <p>{t('signupConfirmationIsMandatory')}</p>
        ) : (
          <>
            <Radio
              id="signup_confirmation-enabled"
              checked={enabled === '1'}
              value="1"
              onCheck={handleChange}
              automationId="enable_signup_confirmation"
            />
            {t('yes')}{' '}
            <Radio
              checked={enabled === ''}
              value=""
              onCheck={handleChange}
              automationId="disable_signup_confirmation"
            />
            {t('no')}
          </>
        )}
      </Inputs>
    </>
  );
}
