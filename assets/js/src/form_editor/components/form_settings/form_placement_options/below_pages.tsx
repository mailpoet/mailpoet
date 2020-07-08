import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import Icon from './icons/below_pages_icon';
import FormPlacementOption from './form_placement_option';

const BelowPages = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const placeFormBellowAllPages = formSettings.placeFormBellowAllPages || false;
  const placeFormBellowAllPosts = formSettings.placeFormBellowAllPosts || false;

  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={placeFormBellowAllPages || placeFormBellowAllPosts}
      label={MailPoet.I18n.t('placeFormBellowPages')}
      icon={Icon}
      onClick={() => (showPlacementSettings('below_post'))}
      canBeActive
    />
  );
};

export default BelowPages;
