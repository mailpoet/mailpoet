import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './below_pages_icon';

const BelowPages = () => {
  const placeFormBellowAllPages = useSelect(
    (select) => select('mailpoet-form-editor').placeFormBellowAllPages(),
    []
  );

  const placeFormBellowAllPosts = useSelect(
    (select) => select('mailpoet-form-editor').placeFormBellowAllPosts(),
    []
  );

  return (
    <FormPlacementOption
      label={MailPoet.I18n.t('placeFormBellowPages')}
      icon={Icon}
      active={placeFormBellowAllPages || placeFormBellowAllPosts}
    />
  );
};

export default BelowPages;
