import React from 'react';
import MailPoet from 'mailpoet';
import Toggle from 'common/toggle';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl } from '@wordpress/components';
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
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="placeFixedBarFormOnAllPages"
            checked={formSettings.placeFixedBarFormOnAllPages || false}
            onCheck={partial(updateSettings, 'placeFixedBarFormOnAllPages')}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="placeFixedBarFormOnAllPosts"
            checked={formSettings.placeFixedBarFormOnAllPosts || false}
            onCheck={partial(updateSettings, 'placeFixedBarFormOnAllPosts')}
          />
        </div>
      </div>
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
