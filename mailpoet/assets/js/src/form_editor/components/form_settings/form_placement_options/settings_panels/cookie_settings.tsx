import React from 'react';
import MailPoet from 'mailpoet';
import { assocPath, compose, __ } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';

type Props = {
  settingsPlacementKey: string;
}

const CookieSettings: React.FunctionComponent<Props> = ({ settingsPlacementKey }: Props) => {
  const cookieExpirationValues = [3, 7, 14, 30, 60, 90];
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  return (
    <SelectControl
      label={MailPoet.I18n.t('formPlacementCookieExpiration')}
      value={formSettings.formPlacement[settingsPlacementKey].cookieExpiration}
      options={[
        { value: 0, label: MailPoet.I18n.t('formPlacementCookieExpirationAlways') },
        { value: 1, label: MailPoet.I18n.t('formPlacementCookieExpirationDay') },
        ...cookieExpirationValues.map((cookieExpirationValue) => ({
          value: cookieExpirationValue,
          label: MailPoet.I18n.t('formPlacementCookieExpirationDays').replace('%1s', cookieExpirationValue),
        }))]}
      onChange={compose([changeFormSettings, assocPath(`formPlacement.${settingsPlacementKey}.cookieExpiration`, __, formSettings)])}
    />
  );
};

export default CookieSettings;
