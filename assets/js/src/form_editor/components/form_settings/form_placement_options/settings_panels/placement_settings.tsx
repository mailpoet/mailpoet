import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { assocPath, compose, __ } from 'lodash/fp';

type Props = {
  settingsPlacementKey: string
}

const PlacementSettings = ({ settingsPlacementKey }: Props) => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  return (
    <>
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPages')}
        checked={formSettings.formPlacement[settingsPlacementKey].pages.all}
        onChange={compose([changeFormSettings, assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, __, formSettings)])}
      />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPosts')}
        checked={formSettings.formPlacement[settingsPlacementKey].posts.all}
        onChange={compose([changeFormSettings, assocPath(`formPlacement.${settingsPlacementKey}.posts.all`, __, formSettings)])}
      />
    </>
  );
};

export default PlacementSettings;
