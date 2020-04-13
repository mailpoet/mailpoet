import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementSettings from './form_placement_settings';
import Toggle from '../../../../common/toggle';
import Icon from './popup_icon';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const Popup = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const popupFormDelay = formSettings.popupFormDelay === undefined
    ? 15
    : formSettings.popupFormDelay;
  const placePopupFormOnAllPages = formSettings.placePopupFormOnAllPages || false;
  const placePopupFormOnAllPosts = formSettings.placePopupFormOnAllPosts || false;

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const [
    localPlacePopupFormOnAllPages,
    setLocalPlacePopupFormOnAllPages,
  ] = useState(placePopupFormOnAllPages);
  const [
    localPlacePopupFormOnAllPosts,
    setLocalPlacePopupFormOnAllPosts,
  ] = useState(placePopupFormOnAllPosts);
  const [
    localDelay,
    setLocalDelay,
  ] = useState(popupFormDelay);

  const save = () => {
    changeFormSettings({
      ...formSettings,
      placePopupFormOnAllPages: localPlacePopupFormOnAllPages,
      placePopupFormOnAllPosts: localPlacePopupFormOnAllPosts,
      popupFormDelay: localDelay,
    });
  };

  return (
    <FormPlacementSettings
      active={placePopupFormOnAllPages || placePopupFormOnAllPosts}
      onSave={save}
      description={MailPoet.I18n.t('placePopupFormOnPagesDescription')}
      label={MailPoet.I18n.t('placePopupFormOnPages')}
      icon={Icon}
    >
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlacePopupFormOnAllPages"
            checked={localPlacePopupFormOnAllPages}
            onCheck={setLocalPlacePopupFormOnAllPages}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlacePopupFormOnAllPosts"
            checked={localPlacePopupFormOnAllPosts}
            onCheck={setLocalPlacePopupFormOnAllPosts}
          />
        </div>
      </div>
      <SelectControl
        label={MailPoet.I18n.t('formPlacementDelay')}
        value={localDelay}
        onChange={setLocalDelay}
        options={delayValues.map((delayValue) => ({
          value: delayValue,
          label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
        }))}
      />
    </FormPlacementSettings>
  );
};

export default Popup;
