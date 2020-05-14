import React from 'react';
import MailPoet from 'mailpoet';
import Toggle from 'common/toggle';
import { useSelect, useDispatch } from '@wordpress/data';
import { partial } from 'lodash';

const BelowPostsSettings = () => {
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

  return (
    <div className="mailpoet-toggle-list">
      <div className="mailpoet-toggle-list-description">
        {MailPoet.I18n.t('placeFormBellowAllPages')}
      </div>
      <div className="mailpoet-toggle-list-toggle">
        <Toggle
          name="placeFormBellowAllPages"
          checked={formSettings.placeFormBellowAllPages || false}
          onCheck={partial(updateSettings, 'placeFormBellowAllPages')}
        />
      </div>
      <div className="mailpoet-toggle-list-description">
        {MailPoet.I18n.t('placeFormBellowAllPosts')}
      </div>
      <div className="mailpoet-toggle-list-toggle" data-automation-id="place-form-bellow-all-posts-toggle">
        <Toggle
          name="placeFormBellowAllPosts"
          checked={formSettings.placeFormBellowAllPosts || false}
          onCheck={partial(updateSettings, 'placeFormBellowAllPosts')}
        />
      </div>
    </div>
  );
};

export default BelowPostsSettings;
