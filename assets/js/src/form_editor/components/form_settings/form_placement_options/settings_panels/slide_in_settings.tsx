import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl, ToggleControl } from '@wordpress/components';
import { partial } from 'lodash';
import { SizeSettings } from 'form_editor/components/size_settings';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const SlideInSettings = () => {
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

  const isActive = formSettings.placeSlideInFormOnAllPages
    || formSettings.placeSlideInFormOnAllPosts;

  return (
    <>
      <p>{MailPoet.I18n.t('placeSlideInFormOnPagesDescription')}</p>
      <hr />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPages')}
        checked={formSettings.placeSlideInFormOnAllPages || false}
        onChange={partial(updateSettings, 'placeSlideInFormOnAllPages')}
      />
      <ToggleControl
        label={MailPoet.I18n.t('placeFormOnAllPosts')}
        checked={formSettings.placeSlideInFormOnAllPosts || false}
        onChange={partial(updateSettings, 'placeSlideInFormOnAllPosts')}
      />
      {isActive && (
        <>
          <SelectControl
            label={MailPoet.I18n.t('formPlacementDelay')}
            value={formSettings.slideInFormDelay}
            onChange={partial(updateSettings, 'slideInFormDelay')}
            options={delayValues.map((delayValue) => ({
              value: delayValue,
              label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
            }))}
          />
          <RadioControl
            label={MailPoet.I18n.t('formPlacementPlacementPosition')}
            selected={formSettings.slideInFormPosition}
            options={[
              { label: MailPoet.I18n.t('formPlacementPlacementPositionLeft'), value: 'left' },
              { label: MailPoet.I18n.t('formPlacementPlacementPositionRight'), value: 'right' },
            ]}
            onChange={partial(updateSettings, 'slideInFormPosition')}
          />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.slideInStyles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width) => (
              updateSettings('slideInStyles', { ...formSettings.slideInStyles, width })
            )}
          />
        </>
      )}
    </>
  );
};

export default SlideInSettings;
