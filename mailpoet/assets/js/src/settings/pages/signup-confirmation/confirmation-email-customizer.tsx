import { t } from 'common/functions';
import { Button } from 'common/button/button';
import { Checkbox } from 'common/form/checkbox/checkbox';
import { Inputs, Label } from 'settings/components';
import { useAction, useSetting } from 'settings/store/hooks';

export function ConfirmationEmailCustomizer() {
  const [signupConfirmationIsEnabled] = useSetting(
    'signup_confirmation',
    'enabled',
  );
  const [
    enableConfirmationEmailCustomizer,
    setEnabledConfirmationEmailCustomizer,
  ] = useSetting('signup_confirmation', 'use_mailpoet_editor');
  const [newsletterId] = useSetting(
    'signup_confirmation',
    'transactional_email_id',
  );

  const openEmailCustomizer = useAction('openEmailCustomizer');
  const openEditor = () => openEmailCustomizer(newsletterId);

  if (!signupConfirmationIsEnabled) return null;

  return (
    <>
      <Label
        title={t('emailCustomizerTitle')}
        description={t('emailCustomizerDescription')}
        htmlFor="mailpoet_confirmation_email_customizer"
      />
      <Inputs>
        <Checkbox
          id="mailpoet_confirmation_email_customizer"
          automationId="mailpoet_confirmation_email_customizer"
          checked={enableConfirmationEmailCustomizer === '1'}
          onCheck={(isChecked) =>
            setEnabledConfirmationEmailCustomizer(isChecked ? '1' : '')
          }
        />
        <div className="mailpoet-settings-inputs-row">
          <Button
            type="button"
            onClick={openEditor}
            variant="secondary"
            dimension="small"
          >
            {t('openTemplateEditor')}
          </Button>
        </div>
      </Inputs>
    </>
  );
}
