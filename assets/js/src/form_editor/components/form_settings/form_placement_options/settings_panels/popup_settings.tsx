import React from 'react';
import MailPoet from 'mailpoet';
import { SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { assocPath, compose, __ } from 'lodash/fp';
import { SizeSettings } from 'form_editor/components/size_settings';
import Heading from '../../../../../common/typography/heading/heading';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const PopUpSettings = () => {
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
          <ToggleControl
            label={MailPoet.I18n.t('placeFormOnAllPages')}
            checked={formSettings.formPlacement.popup.pages.all}
            onChange={compose([changeFormSettings, assocPath('formPlacement.popup.pages.all', __, formSettings)])}
          />
          <ToggleControl
            label={MailPoet.I18n.t('placeFormOnAllPosts')}
            checked={formSettings.formPlacement.popup.posts.all}
            onChange={compose([changeFormSettings, assocPath('formPlacement.popup.posts.all', __, formSettings)])}
          />
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
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.popup.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width) => (
              changeFormSettings(assocPath('formPlacement.popup.styles.width', width, formSettings))
            )}
          />
        </>
      )}
    </>
  );
};

export default PopUpSettings;
