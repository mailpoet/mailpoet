import React from 'react';
import MailPoet from 'mailpoet';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { assocPath, compose, __ } from 'lodash/fp';
import { SizeSettings } from 'form_editor/components/size_settings';
import AnimationSettings from './animation_settings';
import PlacementSettings from './placement_settings';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const PopUpSettings: React.FunctionComponent = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const isActive = formSettings.formPlacement.popup.enabled;

  return (
    <>
      <p>{MailPoet.I18n.t('placePopupFormOnPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([changeFormSettings, assocPath('formPlacement.popup.enabled', __, formSettings)])}
      />
      {isActive && (
        <>
          <hr />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.popup.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width): void => (
              changeFormSettings(assocPath('formPlacement.popup.styles.width', width, formSettings))
            )}
          />
          <PlacementSettings settingsPlacementKey="popup" />
          <AnimationSettings settingsPlacementKey="popup" />
          <SelectControl
            label={MailPoet.I18n.t('formPlacementDelay')}
            value={formSettings.formPlacement.popup.delay}
            onChange={compose([changeFormSettings, assocPath('formPlacement.popup.delay', __, formSettings)])}
            options={delayValues.map((delayValue) => ({
              value: delayValue,
              label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
            }))}
          />
          <div>
            <p><b>{MailPoet.I18n.t('exitIntentTitle')}</b></p>
            <p>{MailPoet.I18n.t('exitIntentDescription')}</p>
            <ToggleControl
              label={MailPoet.I18n.t('exitIntentSwitch')}
              checked={formSettings.formPlacement.popup.exitIntentEnabled}
              onChange={compose([changeFormSettings, assocPath('formPlacement.popup.exitIntentEnabled', __, formSettings)])}
            />
          </div>
        </>
      )}
    </>
  );
};

export default PopUpSettings;
