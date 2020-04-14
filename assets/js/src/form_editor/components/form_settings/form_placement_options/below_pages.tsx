import React, { useState } from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementSettings from './form_placement_settings';
import Toggle from '../../../../common/toggle';
import Icon from './icons/below_pages_icon';

const BelowPages = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const placeFormBellowAllPages = formSettings.placeFormBellowAllPages || false;
  const placeFormBellowAllPosts = formSettings.placeFormBellowAllPosts || false;

  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const [
    localPlaceFormBellowAllPages,
    setLocalPlaceFormBellowAllPages,
  ] = useState(placeFormBellowAllPages);
  const [
    localPlaceFormBellowAllPosts,
    setLocalPlaceFormBellowAllPosts,
  ] = useState(placeFormBellowAllPosts);

  const save = () => {
    changeFormSettings({
      ...formSettings,
      placeFormBellowAllPages: localPlaceFormBellowAllPages,
      placeFormBellowAllPosts: localPlaceFormBellowAllPosts,
    });
  };

  return (
    <FormPlacementSettings
      active={placeFormBellowAllPages || placeFormBellowAllPosts}
      onSave={save}
      description={MailPoet.I18n.t('placeFormBellowPagesDescription')}
      label={MailPoet.I18n.t('placeFormBellowPages')}
      icon={Icon}
    >
      <div className="mailpoet-toggle-list">
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPages')}
        </div>
        <div className="mailpoet-toggle-list-toggle">
          <Toggle
            name="localPlaceFormBellowAllPages"
            checked={localPlaceFormBellowAllPages}
            onCheck={setLocalPlaceFormBellowAllPages}
          />
        </div>
        <div className="mailpoet-toggle-list-description">
          {MailPoet.I18n.t('placeFormBellowAllPosts')}
        </div>
        <div className="mailpoet-toggle-list-toggle" data-automation-id="place-form-bellow-all-posts-toggle">
          <Toggle
            name="localPlaceFormBellowAllPosts"
            checked={localPlaceFormBellowAllPosts}
            onCheck={setLocalPlaceFormBellowAllPosts}
          />
        </div>
      </div>
    </FormPlacementSettings>
  );
};

export default BelowPages;
