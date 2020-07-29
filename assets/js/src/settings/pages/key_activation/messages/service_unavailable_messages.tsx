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
        <p>
          <a
            href="https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key"
            target="_blank"
            rel="noopener noreferrer"
            data-beacon-article="5ef1da9d2c7d3a10cba966c5"
            className="mailpoet_error"
          >
            {MailPoet.I18n.t('learnMore')}
          </a>
        </p>
      </div>
    </>
  );
}
