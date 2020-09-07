import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { assocPath, compose, __ } from 'lodash/fp';
import Selection from 'form/fields/selection.jsx';

type Props = {
  settingsPlacementKey: string
}

const PlacementSettings = ({ settingsPlacementKey }: Props) => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const pages = useSelect(
    (select) => select('mailpoet-form-editor').getAllWPPages(),
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
      <Selection
        item={{
          pages: formSettings.formPlacement[settingsPlacementKey].pages.selected,
        }}
        onValueChange={(e) => {
          changeFormSettings(
            assocPath(
              `formPlacement.${settingsPlacementKey}.pages.selected`,
              e.target.value,
              formSettings
            )
          );
        }}
        field={{
          id: 'pages',
          name: 'pages',
          values: pages,
          multiple: true,
          placeholder: MailPoet.I18n.t('selectPage'),
          getLabel: (seg) => seg.name,
        }}
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
