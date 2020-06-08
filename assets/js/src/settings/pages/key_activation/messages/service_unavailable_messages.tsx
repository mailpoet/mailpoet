import React from 'react';
import MailPoet from 'mailpoet';

export default function ServiceUnavailableMessages() {
  return (
    <>
      <div className="mailpoet_error_item mailpoet_error">
        {MailPoet.I18n.t('premiumTabKeyCannotValidate')}
        <ul className="disc-inside-list">
          <li>{MailPoet.I18n.t('premiumTabKeyCannotValidateLocalhost')}</li>
          <li>{MailPoet.I18n.t('premiumTabKeyCannotValidateBlockingHost')}</li>
          <li>{MailPoet.I18n.t('premiumTabKeyCannotValidateIntranet')}</li>
        </ul>
      </div>
    </>
  );
}
