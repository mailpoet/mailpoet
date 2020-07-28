import React from 'react';
import MailPoet from 'mailpoet';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';
import { SizeSettings } from 'form_editor/components/size_settings';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const PopUpSettings = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const updateSettings = (key, value) => {
    const settings = { ...formSettings };
    settings[key] = value;
    changeFormSettings(settings);
  };

  const isActive = formSettings.placePopupFormOnAllPages || formSettings.placePopupFormOnAllPosts;

  return (
    <>
      <p>{MailPoet.I18n.t('placePopupFormOnPagesDescription')}</p>
      <hr />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPages')}
        checked={formSettings.placePopupFormOnAllPages || false}
        onChange={partial(updateSettings, 'placePopupFormOnAllPages')}
      />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPosts')}
        checked={formSettings.placePopupFormOnAllPosts || false}
        onChange={partial(updateSettings, 'placePopupFormOnAllPosts')}
      />
      {isActive && (
        <>
          <SelectControl
            label={MailPoet.I18n.t('formPlacementDelay')}
            value={formSettings.popupFormDelay}
            onChange={partial(updateSettings, 'popupFormDelay')}
            options={delayValues.map((delayValue) => ({
              value: delayValue,
              label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
            }))}
          />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.popupStyles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width) => (
              updateSettings('popupStyles', { ...formSettings.popupStyles, width })
            )}
          />
        </>
      )}
    </>
  );
};

export default PopUpSettings;
