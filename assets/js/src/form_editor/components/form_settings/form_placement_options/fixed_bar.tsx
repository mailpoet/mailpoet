import React from 'react';
import MailPoet from 'mailpoet';
import { useSelect, useDispatch } from '@wordpress/data';

import FormPlacementOption from './form_placement_option';
import Icon from './icons/fixed_bar_icon';

const FixedBar = () => {
  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );
  const { showPlacementSettings } = useDispatch('mailpoet-form-editor');

  return (
    <FormPlacementOption
      active={formSettings.placementFixedBarEnabled}
      label={MailPoet.I18n.t('placeFixedBarFormOnPages')}
      icon={Icon}
      onClick={() => (showPlacementSettings('fixed_bar'))}
      canBeActive
    />
  );
};

export default FixedBar;
