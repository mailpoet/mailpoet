import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl, ToggleControl } from '@wordpress/components';
import { assocPath, compose, __ } from 'lodash/fp';
import { SizeSettings } from 'form_editor/components/size_settings';
import AnimationSettings from './animation_settings';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const FixedBarSettings = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const isActive = formSettings.formPlacement.fixedBar.enabled;

  return (
    <>
      <p>{MailPoet.I18n.t('placeFixedBarFormOnPagesDescription')}</p>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([changeFormSettings, assocPath('formPlacement.fixedBar.enabled', __, formSettings)])}
      />
      {isActive && (
        <>
          <hr />
          <ToggleControl
            label={MailPoet.I18n.t('placeFormOnAllPages')}
            checked={formSettings.formPlacement.fixedBar.pages.all}
            onChange={compose([changeFormSettings, assocPath('formPlacement.fixedBar.pages.all', __, formSettings)])}
          />
          <ToggleControl
            label={MailPoet.I18n.t('placeFormOnAllPosts')}
            checked={formSettings.formPlacement.fixedBar.posts.all}
            onChange={compose([changeFormSettings, assocPath('formPlacement.fixedBar.posts.all', __, formSettings)])}
          />
          <SelectControl
            label={MailPoet.I18n.t('formPlacementDelay')}
            value={formSettings.formPlacement.fixedBar.delay}
            onChange={compose([changeFormSettings, assocPath('formPlacement.fixedBar.delay', __, formSettings)])}
            options={delayValues.map((delayValue) => ({
              value: delayValue,
              label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
            }))}
          />
          <RadioControl
            label={MailPoet.I18n.t('formPlacementPlacementPosition')}
            selected={formSettings.formPlacement.fixedBar.position}
            options={[
              { label: MailPoet.I18n.t('formPlacementPlacementPositionTop'), value: 'top' },
              { label: MailPoet.I18n.t('formPlacementPlacementPositionBottom'), value: 'bottom' },
            ]}
            onChange={compose([changeFormSettings, assocPath('formPlacement.fixedBar.position', __, formSettings)])}
          />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.fixedBar.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width) => (
              changeFormSettings(assocPath('formPlacement.fixedBar.styles.width', width, formSettings))
            )}
          />
          <AnimationSettings settingsPlacementKey="fixedBar" />
        </>
      )}
    </>
  );
};

export default FixedBarSettings;
