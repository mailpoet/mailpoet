import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import Toggle from 'common/toggle';
import { SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';

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
  const popupFormDelay = formSettings.popupFormDelay === undefined
    ? 15
    : formSettings.popupFormDelay;

  return (
    <>
      <p>{MailPoet.I18n.t('placePopupFormOnPagesDescription')}</p>
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="placePopupFormOnAllPages"
            checked={formSettings.placePopupFormOnAllPages || false}
            onCheck={partial(updateSettings, 'placePopupFormOnAllPages')}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="placePopupFormOnAllPosts"
            checked={formSettings.placePopupFormOnAllPosts || false}
            onCheck={partial(updateSettings, 'placePopupFormOnAllPosts')}
          />
        </div>
      </div>
      <SelectControl
        label={MailPoet.I18n.t('formPlacementDelay')}
        value={popupFormDelay}
        onChange={partial(updateSettings, 'popupFormDelay')}
        options={delayValues.map((delayValue) => ({
          value: delayValue,
          label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
        }))}
      />
    </>
  );
};

export default PopUpSettings;
