import React from 'react';
import MailPoet from 'mailpoet';
import { useSelector } from 'settings/store/hooks';

const KeyValidMessage = () => (
  <div className="mailpoet_success_item mailpoet_success_item mailpoet_success">
    {MailPoet.I18n.t('premiumTabKeyValidMessage')}
  </div>
);

const KeyNotValidMessage = () => (
  <div className="mailpoet_error_item mailpoet_error">
    {MailPoet.I18n.t('premiumTabKeyNotValidMessage')}
  </div>
);

export default function KeyMessages() {
  const { isKeyValid } = useSelector('getKeyActivationState')();
  return isKeyValid ? <KeyValidMessage /> : <KeyNotValidMessage />;
}
