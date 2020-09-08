import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import {
  assocPath,
  compose,
  __,
  cond,
  identity,
} from 'lodash/fp';
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
        onChange={(newValue) => {
          compose([
            changeFormSettings,
            assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, newValue),
            cond([
              [() => newValue, assocPath(`formPlacement.${settingsPlacementKey}.pages.selected`, [])], // if enabled clear selected pages
              [() => !newValue, identity], // if disabled do nothing
            ]),
          ])(formSettings);
        }}
      />
      <Selection
        item={{
          id: formSettings.formPlacement[settingsPlacementKey].pages.selected.join(),
        }}
        onValueChange={(e) => compose([
          changeFormSettings,
          assocPath(`formPlacement.${settingsPlacementKey}.pages.selected`, e.target.value),
          assocPath(`formPlacement.${settingsPlacementKey}.pages.all`, false), // disable all if some pages are selected
        ])(formSettings)}
        field={{
          id: 'pages',
          name: 'pages',
          values: pages,
          multiple: true,
          placeholder: MailPoet.I18n.t('selectPage'),
          getLabel: (page) => page.name,
          selected: () => formSettings.formPlacement[settingsPlacementKey].pages.selected,
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
