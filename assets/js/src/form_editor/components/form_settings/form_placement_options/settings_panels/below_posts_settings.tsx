import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';
import { assocPath, compose, __ } from 'lodash/fp';
import { SizeSettings } from 'form_editor/components/size_settings';
import PlacementSettings from './placement_settings';

const BelowPostsSettings = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const isActive = formSettings.formPlacement.belowPosts.enabled;

  return (
    <>
      <ToggleControl
        label={MailPoet.I18n.t('enable')}
        checked={isActive}
        onChange={compose([changeFormSettings, assocPath('formPlacement.belowPosts.enabled', __, formSettings)])}
      />
      {isActive && (
        <>
          <PlacementSettings settingsPlacementKey="belowPosts" />
          <SizeSettings
            label={MailPoet.I18n.t('formSettingsWidth')}
            value={formSettings.formPlacement.belowPosts.styles.width}
            minPixels={200}
            maxPixels={1200}
            minPercents={10}
            maxPercents={100}
            defaultPixelValue={560}
            defaultPercentValue={100}
            onChange={(width) => (
              changeFormSettings(assocPath('formPlacement.belowPosts.styles.width', width, formSettings))
            )}
          />
        </>
      )}
    </>
  );
};

export default BelowPostsSettings;
