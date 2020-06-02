import React from 'react';
import Button from 'common/button/button';
import MailPoet from 'mailpoet';

type Props = {
  onProceed?: () => any,
}

export default ({ onProceed }: Props) => (
  <div className="mailpoet-offer-clearout-step-container">
    <p>{MailPoet.I18n.t('offerClearoutText1')}</p>
    <p>{MailPoet.I18n.t('offerClearoutText2')}</p>
    <p>{MailPoet.I18n.t('offerClearoutText3')}</p>
    <p>
      <Button
        dimension="small"
        variant="dark"
        target="_blank"
        href="https://clearout.io/?ref=mailpoet"
      >
        {MailPoet.I18n.t('tryClearout')}
      </Button>
      {onProceed && (
        <Button
          onClick={onProceed}
          variant="link-dark"
          dimension="small"
        >
          {MailPoet.I18n.t('clearoutGotIt')}
        </Button>
      )}
    </p>
  </div>
);
