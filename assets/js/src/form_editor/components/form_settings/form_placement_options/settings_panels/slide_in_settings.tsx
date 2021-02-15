import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl, ToggleControl } from '@wordpress/components';
import { assocPath, compose, __ } from 'lodash/fp';
import { SizeSettings } from 'form_editor/components/size_settings';
import AnimationSettings from './animation_settings';
import PlacementSettings from './placement_settings';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const SlideInSettings: React.FunctionComponent = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const isActive = formSettings.formPlacement.slideIn.enabled;

  return (
    <>
      <p>{MailPoet.I18n.t('placeSlideInFormOnPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([changeFormSettings, assocPath('formPlacement.slideIn.enabled', __, formSettings)])}
      />
      {isActive && (
        <>
          <hr />
          <RadioControl
            label={MailPoet.I18n.t('formPlacementPlacementPosition')}
            selected={formSettings.formPlacement.slideIn.position}
            options={[
              { label: MailPoet.I18n.t('formPlacementPlacementPositionLeft'), value: 'left' },
              { label: MailPoet.I18n.t('formPlacementPlacementPositionRight'), value: 'right' },
            ]}
            onChange={compose([changeFormSettings, assocPath('formPlacement.slideIn.position', __, formSettings)])}
          />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.slideIn.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width): void => (
              changeFormSettings(assocPath('formPlacement.slideIn.styles.width', width, formSettings))
            )}
          />
          <PlacementSettings settingsPlacementKey="slideIn" />
          <AnimationSettings settingsPlacementKey="slideIn" />
          <SelectControl
            label={MailPoet.I18n.t('formPlacementDelay')}
            value={formSettings.formPlacement.slideIn.delay}
            onChange={compose([changeFormSettings, assocPath('formPlacement.slideIn.delay', __, formSettings)])}
            options={delayValues.map((delayValue) => ({
              value: delayValue,
              label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
            }))}
          />
        </>
      )}
    </>
  );
};

export default SlideInSettings;
