import React from 'react';
import MailPoet from 'mailpoet';

import FormPlacementSettings from './form_placement_settings';
import Icon from './icons/sidebar_icon';

const Popup = () => {
  return (
    <FormPlacementSettings
      active={false}
      onSave={() => {}}
      header={MailPoet.I18n.t('formPlacementOther')}
      label={MailPoet.I18n.t('formPlacementOtherLabel')}
      icon={Icon}
    >
      text text
    </FormPlacementSettings>
  );
};

export default Popup;
