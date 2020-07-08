import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './icons/popup_icon';

const Popup = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const placePopupFormOnAllPages = formSettings.placePopupFormOnAllPages || false;
  const placePopupFormOnAllPosts = formSettings.placePopupFormOnAllPosts || false;
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={placePopupFormOnAllPages || placePopupFormOnAllPosts}
      label={MailPoet.I18n.t('placePopupFormOnPages')}
      icon={Icon}
      onClick={() => (showPlacementSettings('popup'))}
      canBeActive
    />
  );
};

export default Popup;
