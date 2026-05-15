import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { Inputs, Label } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

export function EmailSharingVisibility() {
  const [defaultVisibility, setDefaultVisibility] = useSetting(
    'sharing',
    'default_visibility',
  );

  return (
    <>
      <Label
        title={t('emailSharingVisibilityTitle')}
        description={t('emailSharingVisibilityDescription')}
        htmlFor=""
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="email-sharing-default-public"
            value="public"
            checked={defaultVisibility === 'public'}
            onCheck={setDefaultVisibility}
            automationId="email-sharing-default-public"
          />
          <label htmlFor="email-sharing-default-public">
            {t('emailSharingPublic')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="email-sharing-default-private"
            value="private"
            checked={defaultVisibility === 'private'}
            onCheck={setDefaultVisibility}
            automationId="email-sharing-default-private"
          />
          <label htmlFor="email-sharing-default-private">
            {t('emailSharingPrivate')}
          </label>
        </div>
      </Inputs>
    </>
  );
}
