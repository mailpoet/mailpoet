import React from 'react';
import MailPoet from 'mailpoet';

import FormPlacementOption from './form_placement_option';
import Icon from './below_pages_icon';

const BelowPages = () => (
  <FormPlacementOption
    label={MailPoet.I18n.t('placeFormBellowPages')}
    icon={Icon}
  />
);

export default BelowPages;
