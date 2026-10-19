import { __, sprintf } from '@wordpress/i18n';
import { Checkbox } from 'common/form/checkbox/checkbox';
import { useSetting, useSelector } from 'settings/store/hooks';
import { Inputs, Label } from 'settings/components';

export function NonProductionSending() {
  const [allowed, setAllowed] = useSetting('allow_sending_in_non_production');
  const environmentType = useSelector('getEnvironmentType');

  if (environmentType === 'production') {
    return null;
  }

  return (
    <>
      <Label
        title={__('Allow sending on this site', 'mailpoet')}
        description={sprintf(
          // translators: %s is the WordPress environment type, for example "staging" or "local"
          __(
            'WordPress reports this site’s type as %s, so MailPoet doesn’t send newsletters, post notifications or automation emails. Turn this on only if this is your live site.',
            'mailpoet',
          ),
          environmentType,
        )}
        htmlFor="mailpoet_allow_sending_in_non_production"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Checkbox
            id="mailpoet_allow_sending_in_non_production"
            automationId="allow-sending-non-production"
            checked={allowed === '1'}
            onCheck={(isChecked) => {
              void setAllowed(isChecked ? '1' : '');
            }}
          />
          <label htmlFor="mailpoet_allow_sending_in_non_production">
            {__('Allow sending', 'mailpoet')}
          </label>
        </div>
      </Inputs>
    </>
  );
}
