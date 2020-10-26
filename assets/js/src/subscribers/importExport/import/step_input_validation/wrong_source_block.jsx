import React from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';

function WrongSourceBlock() {
  return (
    <div
      className="mailpoet-offer-clearout-step-container"
      data-automation-id="import_wrong_source_block"
    >
      <p>{MailPoet.I18n.t('validationStepBlock1')}</p>
      <p>{MailPoet.I18n.t('validationStepBlock2')}</p>
      <p>
        <Button
          href="https://kb.mailpoet.com/article/269-reconfirm-subscribers-to-your-list"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('validationStepBlockButton')}
        </Button>
      </p>
    </div>
  );
}

export default WrongSourceBlock;
