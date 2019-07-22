import React from 'react';
import MailPoet from 'mailpoet';

function OldListBlock() {
  return (
    <div className="mailpoet_import_block">
      <p>{MailPoet.I18n.t('validationStepBlock1')}</p>
      <p>{MailPoet.I18n.t('validationStepBlock3')}</p>
      <p>{MailPoet.I18n.t('validationStepBlock4')}</p>
      <a
        href="https://kb.mailpoet.com/article/269-reconfirm-subscribers-to-your-list"
        target="_blank"
        rel="noopener noreferrer"
        className="button button-primary"
      >
        {MailPoet.I18n.t('validationStepBlockButton')}
      </a>
    </div>
  );
}

export default OldListBlock;
