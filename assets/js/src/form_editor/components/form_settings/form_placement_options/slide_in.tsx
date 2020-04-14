import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { SelectControl, RadioControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementSettings from './form_placement_settings';
import Toggle from '../../../../common/toggle';
import Icon from './icons/popup_icon';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const SlideIn = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const slideInFormDelay = formSettings.slideInFormDelay === undefined
    ? 15
    : formSettings.slideInFormDelay;
  const slideInFormPosition = formSettings.slideInFormPosition === undefined ? 'right' : formSettings.slideInFormPosition;
  const placeSlideInFormOnAllPages = formSettings.placeSlideInFormOnAllPages || false;
  const placeSlideInFormOnAllPosts = formSettings.placeSlideInFormOnAllPosts || false;

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const [
    localPlaceSlideInFormOnAllPages,
    setLocalPlaceSlideInFormOnAllPages,
  ] = useState(placeSlideInFormOnAllPages);
  const [
    localPlaceSlideInFormOnAllPosts,
    setLocalPlaceSlideInFormOnAllPosts,
  ] = useState(placeSlideInFormOnAllPosts);
  const [
    localDelay,
    setLocalDelay,
  ] = useState(slideInFormDelay);
  const [
    localPosition,
    setLocalPosition,
  ] = useState(slideInFormPosition);

  const save = () => {
    changeFormSettings({
      ...formSettings,
      placeSlideInFormOnAllPages: localPlaceSlideInFormOnAllPages,
      placeSlideInFormOnAllPosts: localPlaceSlideInFormOnAllPosts,
      slideInFormDelay: localDelay,
      slideInFormPosition: localPosition,
    });
  };

  return (
    <FormPlacementSettings
      active={placeSlideInFormOnAllPages || placeSlideInFormOnAllPosts}
      onSave={save}
      description={MailPoet.I18n.t('placeSlideInFormOnPagesDescription')}
      label={MailPoet.I18n.t('placeSlideInFormOnPages')}
      icon={Icon}
    >
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlaceSlideInFormOnAllPages"
            checked={localPlaceSlideInFormOnAllPages}
            onCheck={setLocalPlaceSlideInFormOnAllPages}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlaceSlideInFormOnAllPosts"
            checked={localPlaceSlideInFormOnAllPosts}
            onCheck={setLocalPlaceSlideInFormOnAllPosts}
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
      <RadioControl
        label={MailPoet.I18n.t('formPlacementPlacementPosition')}
        selected={localPosition}
        options={[
          { label: MailPoet.I18n.t('formPlacementPlacementPositionLeft'), value: 'left' },
          { label: MailPoet.I18n.t('formPlacementPlacementPositionRight'), value: 'right' },
        ]}
        onChange={setLocalPosition}
      />
    </FormPlacementSettings>
  );
};

export default SlideIn;
