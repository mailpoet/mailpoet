import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { SelectControl } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementSettings from './form_placement_settings';
import Toggle from '../../../../common/toggle';
import Icon from './fixed_bar_icon';

const delayValues = [0, 15, 30, 60, 120, 180, 240];

const FixedBar = () => {
  const fixedBarFormDelay = useSelect(
    (select) => select('mailpoet-form-editor').getFixedBarFormDelay(),
    []
  );

  const placeFixedBarFormOnAllPages = useSelect(
    (select) => select('mailpoet-form-editor').placeFixedBarFormOnAllPages(),
    []
  );

  const placeFixedBarFormOnAllPosts = useSelect(
    (select) => select('mailpoet-form-editor').placeFixedBarFormOnAllPosts(),
    []
  );
  const {
    setPlaceFixedBarFormOnAllPages,
    setPlaceFixedBarFormOnAllPosts,
    setFixedBarFormDelay,
  } = useDispatch('mailpoet-form-editor');

  const [
    localPlaceFixedBarFormOnAllPages,
    setLocalPlaceFixedBarFormOnAllPages,
  ] = useState(placeFixedBarFormOnAllPages);
  const [
    localPlaceFixedBarFormOnAllPosts,
    setLocalPlaceFixedBarFormOnAllPosts,
  ] = useState(placeFixedBarFormOnAllPosts);
  const [
    localDelay,
    setLocalDelay,
  ] = useState(fixedBarFormDelay === undefined ? 15 : fixedBarFormDelay);

  const save = () => {
    setPlaceFixedBarFormOnAllPages(localPlaceFixedBarFormOnAllPages);
    setPlaceFixedBarFormOnAllPosts(localPlaceFixedBarFormOnAllPosts);
    setFixedBarFormDelay(localDelay);
  };

  return (
    <FormPlacementSettings
      active={placeFixedBarFormOnAllPages || placeFixedBarFormOnAllPosts}
      onSave={save}
      description={MailPoet.I18n.t('placeFixedBarFormOnPagesDescription')}
      label={MailPoet.I18n.t('placeFixedBarFormOnPages')}
      icon={Icon}
    >
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFixedBarFormOnPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlaceFixedBarFormOnAllPages"
            checked={localPlaceFixedBarFormOnAllPages}
            onCheck={setLocalPlaceFixedBarFormOnAllPages}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlaceFixedBarFormOnAllPosts"
            checked={localPlaceFixedBarFormOnAllPosts}
            onCheck={setLocalPlaceFixedBarFormOnAllPosts}
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

export default FixedBar;
