import React from 'react';
import MailPoet from 'mailpoet';
import Toggle from 'common/toggle';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl, RadioControl } from '@wordpress/components';
import { partial } from 'lodash';

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

  const slideInFormDelay = formSettings.slideInFormDelay === undefined
    ? 15
    : formSettings.slideInFormDelay;
  const slideInFormPosition = formSettings.slideInFormPosition === undefined ? 'right' : formSettings.slideInFormPosition;

  return (
    <>
      <p>{MailPoet.I18n.t('placeSlideInFormOnPagesDescription')}</p>
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="placeSlideInFormOnAllPages"
            checked={formSettings.placeSlideInFormOnAllPages || false}
            onCheck={partial(updateSettings, 'placeSlideInFormOnAllPages')}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="placeSlideInFormOnAllPosts"
            checked={formSettings.placeSlideInFormOnAllPosts || false}
            onCheck={partial(updateSettings, 'placeSlideInFormOnAllPosts')}
          />
        </div>
      </div>
      <SelectControl
        label={MailPoet.I18n.t('formPlacementDelay')}
        value={slideInFormDelay}
        onChange={partial(updateSettings, 'slideInFormDelay')}
        options={delayValues.map((delayValue) => ({
          value: delayValue,
          label: MailPoet.I18n.t('formPlacementDelaySeconds').replace('%1s', delayValue),
        }))}
      />
      <RadioControl
        label={MailPoet.I18n.t('formPlacementPlacementPosition')}
        selected={slideInFormPosition}
        options={[
          { label: MailPoet.I18n.t('formPlacementPlacementPositionLeft'), value: 'left' },
          { label: MailPoet.I18n.t('formPlacementPlacementPositionRight'), value: 'right' },
        ]}
        onChange={partial(updateSettings, 'slideInFormPosition')}
      />
    </>
  );
};

export default SlideInSettings;
