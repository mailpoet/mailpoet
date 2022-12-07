import { MailPoet } from 'mailpoet';
import { __, assocPath, compose } from 'lodash/fp';
import { useDispatch, useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { withBoundary } from 'common';

type Props = {
  settingsPlacementKey: string;
};

function CookieSettings({ settingsPlacementKey }: Props): JSX.Element {
  const cookieExpirationValues = [3, 7, 14, 30, 60, 90];
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  return (
    <SelectControl
      label={MailPoet.I18n.t('formPlacementCookieExpiration')}
      value={formSettings.formPlacement[settingsPlacementKey].cookieExpiration}
      options={[
        {
          value: '0',
          label: MailPoet.I18n.t('formPlacementCookieExpirationAlways'),
        },
        {
          value: '1',
          label: MailPoet.I18n.t('formPlacementCookieExpirationDay'),
        },
        ...cookieExpirationValues.map((cookieExpirationValue) => ({
          value: `${cookieExpirationValue}`,
          label: MailPoet.I18n.t('formPlacementCookieExpirationDays').replace(
            '%1s',
            cookieExpirationValue.toString(),
          ),
        })),
      ]}
      onChange={compose([
        changeFormSettings,
        assocPath(
          `formPlacement.${settingsPlacementKey}.cookieExpiration`,
          __,
          formSettings,
        ),
      ])}
    />
  );
}

CookieSettings.displayName = 'FormEditorCookieSettings';
const CookieSettingsWithBoundary = withBoundary(CookieSettings);
export { CookieSettingsWithBoundary as CookieSettings };
