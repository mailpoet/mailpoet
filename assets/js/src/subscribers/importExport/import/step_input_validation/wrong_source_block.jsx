import React from 'react';
import MailPoet from 'mailpoet';

function WrongSourceBlock() {
  return (
    <div
      className="mailpoet_import_block"
      data-automation-id="import_wrong_source_block"
    >
      <p>{MailPoet.I18n.t('validationStepBlock1')}</p>
      <p>{MailPoet.I18n.t('validationStepBlock2')}</p>
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

export default WrongSourceBlock;
