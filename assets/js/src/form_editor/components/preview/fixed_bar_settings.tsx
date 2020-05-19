import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl, ToggleControl } from '@wordpress/components';
import { partial } from 'lodash';
import { SizeSettings } from 'form_editor/components/size_settings';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const FixedBarSettings = () => {
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

  const fixedBarFormDelay = formSettings.fixedBarFormDelay === undefined
    ? 15
    : formSettings.fixedBarFormDelay;
  const fixedBarFormPosition = formSettings.fixedBarFormPosition === undefined ? 'top' : formSettings.fixedBarFormPosition;

  return (
    <>
      <p>{MailPoet.I18n.t('placeFixedBarFormOnPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPages')}
        checked={formSettings.placeFixedBarFormOnAllPages || false}
        onChange={partial(updateSettings, 'placeFixedBarFormOnAllPages')}
      />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPosts')}
        checked={formSettings.placeFixedBarFormOnAllPosts || false}
        onChange={partial(updateSettings, 'placeFixedBarFormOnAllPosts')}
      />
      <SelectControl
        label={MailPoet.I18n.t('formPlacementDelay')}
        value={fixedBarFormDelay}
        onChange={partial(updateSettings, 'fixedBarFormDelay')}
        options={delayValues.map((delayValue) => ({
          value: delayValue,
          label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
        }))}
      />
      <RadioControl
        label={MailPoet.I18n.t('formPlacementPlacementPosition')}
        selected={fixedBarFormPosition}
        options={[
          { label: MailPoet.I18n.t('formPlacementPlacementPositionTop'), value: 'top' },
          { label: MailPoet.I18n.t('formPlacementPlacementPositionBottom'), value: 'bottom' },
        ]}
        onChange={partial(updateSettings, 'fixedBarFormPosition')}
      />
      <SizeSettings
        label={MailPoet.I18n.t('formSettingsWidth')}
        value={formSettings.fixedBarStyles.width}
        minPixels={200}
        maxPixels={1200}
        minPercents={10}
        maxPercents={100}
        defaultPixelValue={560}
        defaultPercentValue={100}
        onChange={(width) => (
          updateSettings('fixedBarStyles', { ...formSettings.fixedBarStyles, width })
        )}
      />
    </>
  );
};

export default FixedBarSettings;
