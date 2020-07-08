import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import Icon from './icons/slide_in_icon';
import FormPlacementOption from './form_placement_option';

const SlideIn = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const placeSlideInFormOnAllPages = formSettings.placeSlideInFormOnAllPages || false;
  const placeSlideInFormOnAllPosts = formSettings.placeSlideInFormOnAllPosts || false;
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={placeSlideInFormOnAllPages || placeSlideInFormOnAllPosts}
      label={MailPoet.I18n.t('placeSlideInFormOnPages')}
      icon={Icon}
      onClick={() => (showPlacementSettings('slide_in'))}
      canBeActive
    />
  );
};

export default SlideIn;
