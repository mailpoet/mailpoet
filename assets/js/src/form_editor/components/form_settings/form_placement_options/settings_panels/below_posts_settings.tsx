import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { partial } from 'lodash';
import { SizeSettings } from 'form_editor/components/size_settings';

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

  const isActive = formSettings.placementBellowAllPostsEnabled;

  return (
    <>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={partial(updateSettings, 'placementBellowAllPostsEnabled')}
      />
      {isActive && (
        <>
          <ToggleControl
            label={MailPoet.I18n.t('placeFormOnAllPages')}
            checked={formSettings.placeFormBellowAllPages || false}
            onChange={partial(updateSettings, 'placeFormBellowAllPages')}
          />
          <ToggleControl
            label={MailPoet.I18n.t('placeFormOnAllPosts')}
            checked={formSettings.placeFormBellowAllPosts || false}
            onChange={partial(updateSettings, 'placeFormBellowAllPosts')}
          />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.belowPostStyles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width) => (
              updateSettings('belowPostStyles', { ...formSettings.belowPostStyles, width })
            )}
          />
        </>
      )}
    </>
  );
};

export default BelowPostsSettings;
